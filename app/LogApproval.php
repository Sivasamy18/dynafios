<?php

namespace App;

use App\customClasses\PaymentTypeFactoryClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\customClasses\PaymentFrequencyFactoryClass;
use Auth;
use function App\Start\is_hospital_admin;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;

class LogApproval extends Model
{
    protected $table = 'log_approval';
    const physician = 1;
    const contract_manager = 2;
    const financial_manager = 3;
    const executive_manager = 4;

    public function logsForApproval($user_id, $type, $contract_type = 0, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0)
    {
        return $this->pendingLogsForApproval($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
    }

    private function pendingLogsForApproval($user_id, $type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0)
    {
        $result = array();
        $contracts = $this->getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        foreach ($contracts as $contract) {
            $contract_present = 0;
            //$physician = Physician::findOrFail($contract->physician_id);
            //remove old approval manager check 30Aug2018
            /*if($contract->contract_CM !=0 || $contract->contract_FM !=0 ){
                switch ($type) {
                    case 0:
                        if($contract->contract_CM == $user_id){
                            $contract_present = 1;
                        }
                        break;
                    case 1:
                        if($contract->contract_FM == $user_id){
                            $contract_present = 1;
                        }
                        break;
                }
            }else{
                $contract_present = 1;
            }*/
            // add new approval check 30Aug2018
            if ($contract->default_to_agreement == 0) {
                //Check contract level CM FM
                $approval_manager_data = ApprovalManagerInfo::where("contract_id", "=", $contract->id)
                    ->where("is_deleted", "=", '0')->orderBy('level')->get();
                $agreement_approval_managers_info = ApprovalManagerInfo::where("contract_id", "=", $contract->id)
                    ->where("is_deleted", "=", '0')
                    ->where("user_id", "=", $user_id)
                    ->where("type_id", "=", $type)->first();
                //if (count($agreement_approval_managers_info) > 0) { // Old condition to check if data exists in variable
                if ($agreement_approval_managers_info) { // New condition to check if data exists in variable added by akash
                    $contract_present = 1;
                } else {
                    $contract_present = 0;
                }
            } else {
                //Check contract level CM FM
                $approval_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                    ->where("contract_id", "=", 0)
                    ->where("is_deleted", "=", '0')->orderBy('level')->get();
                $agreement_approval_managers_info = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                    ->where("contract_id", "=", 0)
                    ->where("is_deleted", "=", '0')
                    ->where("user_id", "=", $user_id)
                    ->where("type_id", "=", $type)->first();
                // if (count($agreement_approval_managers_info) > 0) { //Old condition to check if data exists in variable
                if ($agreement_approval_managers_info) { //New condition to check if data exists in variable added by akash
                    $contract_present = 1;
                } else {
                    $contract_present = 0;
                }
            }
            $physician = Physician::where('id', '=', $contract->physician_id)->first();
            if ($physician && $contract_present == 1) {
                //$practice = Practice::findOrFail($contract->practice_id);
                $practice = Practice::where('id', '=', $contract->practice_id)->first();
                if ($practice) {
                    $contractName = ContractName::findOrFail($contract->contract_name_id);
                    $logs = PhysicianLog::select(
                        DB::raw("physician_logs.date as log_date"),
                        DB::raw("physician_logs.duration as duration"),
                        DB::raw("physician_logs.details as log_details"),
                        DB::raw("physician_logs.id as log_id"),
                        DB::raw("actions.name as action"))
                        ->distinct('physician_logs.id')
                        ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                        //->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                        ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
                        ->where("physician_logs.date", "<=", $prior_month_end_date)
                        ->where("physician_logs.signature", "=", 0)
                        ->where("physician_logs.approval_date", "=", "0000-00-00")
                        ->where("physician_logs.practice_id", "=", $contract->practice_id)
                        ->where("physician_logs.contract_id", "=", $contract->id);
                    $logs = $logs->orderBy('physician_logs.date', 'desc')->get();
                    $logsForApproval = array();
                    foreach ($logs as $log) {
                        //Remove check 28Aug2018
                        /*$add = 0;
                        $approval = DB::table('log_approval')->select('*')
                            ->where('log_id', '=', $log->log_id)->orderBy('role')->get();
                        foreach ($approval as $approve) {
                            if ($approve->role == 1 && $approve->approval_status == 1 && $add == 0) {
                                $add = 1;
                            } elseif ($approve->role == 2 && $approve->approval_status == 1 && $add == 1) {
                                $add = 2;
                            } elseif ($approve->role == 3 && $approve->approval_status == 1 && $add == 2) {
                                $add = 3;
                            }
                        }
                        switch ($type) {
                            case 0:
                                if ($add == 1) {
                                    $logsForApproval[] = $log;
                                }
                                break;
                            case 1:
                                if ($add == 2) {
                                    $logsForApproval[] = $log;
                                }
                                break;
                        }*/

                        //Add Approval check 28Aug2018
                        $approval = DB::table('log_approval')->select('*')
                            ->where('log_id', '=', $log->log_id)
                            ->where('approval_managers_level', '=', $agreement_approval_managers_info->level)
                            ->where('approval_status', '=', 1)
                            ->get();
                        if (count($approval) == 0) {
                            $approval_prev_level = DB::table('log_approval')->select('*')
                                ->where('log_id', '=', $log->log_id)
                                ->where('approval_managers_level', '=', $agreement_approval_managers_info->level - 1)
                                ->where('approval_status', '=', 1)
                                ->orderBy("approval_managers_level")
                                ->get();
                            if (count($approval_prev_level) == 1) {
//                                $approval_prev_levels = DB::table('log_approval')->select('*')
//                                    ->where('log_id', '=', $log->log_id)
//                                    ->where('approval_managers_level', '<=', $agreement_approval_managers_info->level - 1)
//                                    ->where('approval_status', '=', 1)
//                                    ->orderBy("approval_managers_level")
//                                    ->get();
//                                $approvedCount = 0;
//                                $level = ["NA", "NA", "NA", "NA", "NA", "NA", "NA"];
//                                foreach ($approval_prev_levels as $prelevel) {
//                                    if ($prelevel->approval_managers_level > 0) {
//                                        $user = User::findOrFail($prelevel->user_id);
//                                        $level[] = [
//                                            "status" => "Approved",
//                                            "name" => $user->first_name . " " . $user->last_name
//                                        ];
//                                    }
//                                    $approvedCount = $prelevel->approval_managers_level;
//                                }
//                                if (count($approval_manager_data) != $approvedCount) {
//                                    for ($c = $approvedCount; $c <= count($approval_manager_data); $c++) {
//                                        $user = User::findOrFail($approval_manager_data[$c]->user_id);
//                                        $level[] = [
//                                            "status" => "Pending",
//                                            "name" => $user->first_name . " " . $user->last_name
//                                        ];
//                                    }
//                                }
                                $logsForApproval[] = $log;
                            }
                        }
                    }
                    $hospital = Hospital::findOrFail($contract->hospital_id);
                    $data['hospital_id'] = $contract->hospital_id;
                    $data['hospital_name'] = $hospital->name;
                    $data['agreement_name'] = $contract->agreement_name;
                    $data['physician_name'] = $physician->first_name . ' ' . $physician->last_name;
                    $data['contract_name_id'] = $contractName->id;
                    $data['contract_name'] = $contractName->name;
                    $data['contract_id'] = $contract->id;
                    $data['practice_id'] = $contract->practice_id;
                    $data['practice_name'] = $practice->name;
                    $data['logs'] = $logsForApproval;
                    if (count($logsForApproval) > 0) {
                        $result[] = $data;
                    }
                } else {
                }
            } else {
            }
        }
        return $result;
    }

    public function getReasonsList()
    {
        $reasons = DB::table('rejected_log_reasons')->select('*')->where('is_custom_reason', '=', 0)->pluck("reason", "id");
        return self::reasonDefaultOptions($reasons);
        //return $reasons;
    }

    public static function saveRejectedLogReason($reason)
    {
        $rejected_log_reason_id = DB::table("rejected_log_reasons")->insertGetId(["reason" => $reason, "is_custom_reason" => 1]);
        return $rejected_log_reason_id;
    }

    public function getApprovalManagers($user_id)
    {
        $defaults = [
            "0" => "All"
        ];

        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        $approval_manager_type = DB::table('agreement_approval_managers_info')->select(
            DB::raw("agreement_approval_managers_info.type_id"))->distinct()
            //->where('agreement_approval_managers_info.user_id', '=', $user_id)
            ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
            ->where('agreement_approval_managers_info.is_deleted', '=', '0')
            ->get();
        //Log::info('approval_manager_type',array($approval_manager_type));
        // Log::info('contract_types',array($contract_types));

        $approval_manager_type_ids = array();
        foreach ($approval_manager_type as $approval_manager_type) {
            $type_id = $approval_manager_type->type_id;
            /*$contract_types=Hospital::fetch_contract_stats_for_hospital_users($user_id);
            foreach ($contract_types as $contract_types) {
              $contract_type_id=$contract_types['contract_type_id'];
              $logs=$this->pendingLogsForApproval($user_id,$type_id,$contract_type_id);
              if(count($logs)>0)
              {*/
            if (!in_array($type_id, $approval_manager_type_ids)) {
                array_push($approval_manager_type_ids, $type_id);
            }
            /*}
          }*/
        }
        if (count($approval_manager_type_ids) > 0) {
            $approval_manager_types = ApprovalManagerType::whereIn("approval_manager_type_id", $approval_manager_type_ids)
                ->pluck('manager_type', 'approval_manager_type_id')->toArray();
            return $defaults + $approval_manager_types;
        } else {
            $approval_manager_types = array();
            return $defaults + $approval_manager_types;
        }

    }

    private static function reasonDefaultOptions($results)
    {

        $defaults = [
            "0" => "Select Reason"
        ];

        return $defaults + $results->toArray();
    }

    private static function getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id)
    {
        $practiceArray = array();
        if (Auth::check()) {
            if (is_practice_manager()) {
                $practice = DB::table('practice_user')
                    ->where('user_id', '=', $user_id)
                    ->select('practice_id')->get();
                foreach ($practice as $practiceID) {
                    $practiceArray[] = $practiceID->practice_id;
                }
            }
        }
        if (count($practiceArray) == 0) {
            $practiceArray[] = 0;
        }
        $contracts = DB::table('contracts')->select(
            DB::raw("contracts.*"),
            DB::raw("physician_practice_history.practice_id as practice_id"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("agreements.approval_process as approval_process"))
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id');
        if ($type != -1) {
            $contracts = $contracts->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id');
        } else {
            if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
                $contracts = $contracts->join('practices', 'practices.hospital_id', '=', 'agreements.hospital_id');
                $contracts = $contracts->join('practice_user', 'practice_user.practice_id', '=', 'practices.id');
            } else {
                $contracts = $contracts->join('hospital_user', 'hospital_user.hospital_id', '=', 'agreements.hospital_id');
            }
        }
        $contracts = $contracts->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id');

        if ($type != -1) {
            $contracts = $contracts->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->where('agreement_approval_managers_info.is_deleted', '=', '0');
            if ($type != 0) {
                $contracts = $contracts->where('agreement_approval_managers_info.type_id', '=', $type);
            }
        } else {
            if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
                $contracts = $contracts->where('practice_user.user_id', '=', $user_id);
            } else {
                $contracts = $contracts->where('hospital_user.user_id', '=', $user_id);
            }
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $contracts = $contracts->where('agreements.hospital_id', '=', $hospital_id);
        }
        if (Auth::check()) {
            if (is_practice_manager()) {
                $contracts = $contracts->whereIn('physician_practice_history.practice_id', $practiceArray);
            }
        }

        if ($agreement_id != 0) {
            $contracts = $contracts->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $contracts = $contracts->where('physician_practice_history.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $contracts = $contracts->where('contracts.physician_id', '=', $physician_id);
        }

        //$contracts = $contracts->orderBy('contracts.contract_name_id')->orderBy('physician_practice_history.practice_id')->get();
        $contracts = $contracts->where('agreements.archived', '=', false)->orderBy('physician_practice_history.physician_id')
            ->orderBy('physician_practice_history.practice_id')->orderBy('contract_names.name')->distinct()->get();
        return $contracts;
    }

    public static function LogsForApprover($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $agreement_ids, $contracts_for_user, $report = false, $contract_id = null, $contract_name_id = null, $level_three_flag = false)
    {
        $responceResult = array();
        $result = array();
        $sorted_result = array();
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //$contracts = self::getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        if (count($contracts_for_user) == 0) {
            $agreement_ids = ApprovalManagerInfo::select("agreement_id")
                ->where("is_deleted", "=", '0')
                //->where("user_id", "=", $user_id)
                ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                ->pluck("agreement_id");
            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);
            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->whereNull("contracts.deleted_at")
                //->where("user_id", "=", $user_id)
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");
        }
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_end_date = '0000-00-00';
        foreach ($contracts_for_user as $user_contract_id) {
            $contract_obj = Contract::find($user_contract_id);
            $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                $prior_month_end_date = $res_pay_frequency['prior_date'];
            }
        }

        // log::info('$prior_month_end_date',array($prior_month_end_date));
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");

        // Below condition is added by akash for fetching the contract specific data for physician on level three.
        /*if(isset($contract_id)){
            $contracts_for_user = [$contract_id];
            $dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');
        } else {
            $dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');
        }*/

        $logs = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("if(contracts.payment_type_id=2,1,0) as payment_type_sort"),
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.details as log_details"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("actions.category_id as action_category"),
            DB::raw("physician_logs.signature as approval_signature"),
            DB::raw("physician_logs.approval_date as final_approval_date"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.next_approver_level as next_approver_level"),
            DB::raw("physician_logs.next_approver_user as next_approver_user"),
            DB::raw("physician_logs.entered_by as entered_by"),
            DB::raw("physician_logs.entered_by_user_type as entered_by_user_type"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.log_hours"),
            DB::raw("physicians.first_name as physician_first_name"),
            DB::raw("physicians.last_name as physician_last_name"),
            DB::raw("contracts.default_to_agreement as default_to_agreement"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("contracts.rate"),
            DB::raw("contracts.partial_hours"), // Partial shift hours for add_call_duratio_partial_hours_on_call.
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("hospitals.name as hospital_name"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "=", 0)
            ->whereNull("physician_logs.deleted_at")
            ->where("physician_logs.approval_date", "=", "0000-00-00");

        // This condition is added to use this function for payment status dashboard precalculation where we need to fetch all logs not the user specific logs
        if ($user_id != 0) {
            $logs = $logs->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id)
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            });
        }

        $logs = $logs->where(function ($query1) use ($user_id) {
            $query1->whereNull("physician_logs.deleted_at");
            // ->orWhere("physician_logs.deleted_at", "=", "");
        }); /*added for soft delete*/
//            ->whereIN("physician_logs.contract_id", $contracts_for_user);

        if (isset($contract_id) && $level_three_flag) {
            $logs = $logs->where("physician_logs.contract_id", '=', $contract_id);
        } else {
            $logs = $logs->whereIN("physician_logs.contract_id", $contracts_for_user);
        }

        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        // check if payment_type scrollDown selected. If yes filter the logs
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('physician_logs.physician_id', '=', $physician_id);
        }
        if ($contract_name_id != null) {
            $logs = $logs->where('contracts.contract_name_id', '=', $contract_name_id);
        }
        $logs = $logs->where('agreements.archived', '=', false)
            ->orderBy('physician_logs.date')
            ->orderBy('current_user_status_sort', "DESC")
            ->orderBy('payment_type_sort', "DESC")
            ->orderBy('physician_logs.physician_id')
            ->orderBy('physician_logs.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.next_approver_user', "DESC");

        if ($report) {
            $logs = $logs->get();
        } else {
            $logs = $logs->paginate(10);
        }
        $prev_contract_id = 0;
        // call function for contracts where this user is assigned only as primary approver
        $primary_approver_contracts = Contract::get_primary_approver_contracts($user_id);
        foreach ($logs as $log) {
            $practice = Practice::where('id', '=', $log->practice_id)->first();
            if ($practice) {
                $level = array();
                $logsForApproval = array();
                $logdate = strtotime($log->log_date);
                //if ($log->next_approver_user == $user_id) {
                if (!(in_array($log->contract_id, $primary_approver_contracts))) {
                    $log->proxy = 'true';
                } else {
                    $log->proxy = 'false';
                }
                if (in_array($log->next_approver_user, $proxy_check_id)) {
                    $log->current_user_status = "Waiting";
                    if ($log->next_approver_user != $user_id) {
                        $log->proxy = 'true';
                    }
                    /*if($log->next_approver_user== $user_id)
                    {
                      $log->proxy_approver_true=false;
                    }
                    else
                    {
                      $log->proxy_approver_true=false;
                    }*/
                    $level[] = [
                        "status" => "Approved",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                } else {
                    $log->current_user_status = "Pending";
                    $level[] = [
                        "status" => "Pending",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                }
                if ($prev_contract_id != $log->contract_id) {
                    $approval_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                        ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                        ->where("is_deleted", "=", '0')->orderBy("level")->get();
                    $prev_contract_id = $log->contract_id;
                }
                $log_approval = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", ">", 0)->orderBy("approval_managers_level")->get();
                if (count($log_approval) > 0) {
                    foreach ($log_approval as $approvals) {
                        $user = User::withTrashed()->findOrFail($approvals->user_id); //withTrashed is used to fetch the deleted approvals also to avoid 404 issue on approval dashboard by akash
                        $level[] = [
                            "status" => "Approved",
                            "name" => $user->first_name . ' ' . $user->last_name
                        ];
                    }
                    if (isset($approval_manager_data[$log->next_approver_level - 1])) {
                        $manager_type_id = $approval_manager_data[$log->next_approver_level - 1]->type_id;
                    } else {
                        $manager_type_id = -1;
                    }
                } else {
                    if ($log->next_approver_level > 0) {
                        $approval_current_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                            ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->where("level", "=", $log->next_approver_level)->first();

                        if ($approval_current_manager_data) {
                            $manager_type_id = $approval_current_manager_data->type_id;
                        } else {
                            $manager_type_id = 0;
                        }
                    } else {
                        $manager_type_id = 0;
                    }
                }
                if (count($level) < 7) {
                    for ($la = count($level); $la < 7; $la++) {
                        if (count($approval_manager_data) >= $la) {
                            $user = User::findOrFail($approval_manager_data[$la - 1]->user_id);
                            $user_name = $user->first_name . ' ' . $user->last_name;
                            $u_status = "Pending";
                            $rejection = DB::table('log_approval_history')->select('*')
                                ->where('log_id', '=', $log->log_id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            if ($rejection) {
                                if ($rejection->approval_status == 0 && $rejection->approval_managers_level == $la) {
                                    $user = User::findOrFail($rejection->user_id);
                                    $user_name = $user->first_name . ' ' . $user->last_name;
                                    $u_status = "Rejected";
                                    $isRejected = true;
                                }
                            }
                        } else {
                            $user_name = " ";
                            $u_status = "N/A";
                        }
                        $level[] = [
                            "status" => $u_status,
                            "name" => $user_name
                        ];
                    }
                }
                if (strtotime($temp_start_date) >= $logdate && strtotime($temp_end_date) > $logdate) {
                    $temp_start_date = $log->log_date;
                } elseif ((strtotime($temp_start_date) < $logdate && strtotime($temp_end_date) <= $logdate) || (strtotime($temp_end_date) == strtotime(date("Y-m-d")) && strtotime($temp_end_date) != $logdate)) {
                    $temp_end_date = $log->log_date;
                }
                if ($manager_type_id != -1) {
                    /// CHANGES HERE - if Contract_id and action_id match in
                    // ON CALL cahnge action name
                    //if ($log->contract_type_id == ContractType::ON_CALL){
                    if ($log->payment_type_id == PaymentType::PER_DIEM || $log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $oncallactivity = DB::table('on_call_activities')
                            ->select('name')
                            ->where("contract_id", "=", $log->contract_id)
                            ->where("action_id", "=", $log->action_id)
                            ->first();
                        if ($oncallactivity) {
                            $log->action = $oncallactivity->name;
                        }
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

                    // 6.1.15 custom headings as well as actions Start
                    if ($log->payment_type_id == PaymentType::TIME_STUDY) {
                        $custom_action = CustomCategoryActions::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            ->where('action_id', '=', $log->action_id)
                            ->where('action_name', '!=', null)
                            ->where('is_active', '=', true)
                            ->first();

                        $log->action = $custom_action ? $custom_action->action_name : $log->action;
                    }
                    // 6.1.15 custom headings as well as actions End
                    /////////////////////////////////////
                    $data['hospital_id'] = $log->hospital_id;
                    $data['hospital_name'] = $log->hospital_name;
                    $data['agreement_id'] = $log->agreement_id;
                    $data['agreement_name'] = $log->agreement_name;
                    $data['physician_name'] = $log->physician_first_name . ' ' . $log->physician_last_name;
                    $data['physician_id'] = $log->physician_id;
                    $data['contract_name_id'] = $log->contract_name_id;
                    $data['contract_name'] = $log->contract_name;
                    $data['contract_id'] = $log->contract_id;
                    $data['contract_type_id'] = $log->contract_type_id;
                    $data['practice_id'] = $log->practice_id;
                    $data['practice_name'] = $practice->name;
                    $data['current_user_status'] = $log->current_user_status;
                    $data['log_date'] = $log->log_date;
                    $data['duration'] = $log->duration;
                    $data['log_details'] = $log->log_details;
                    $data['log_id'] = $log->log_id;
                    $data['action'] = $log->action;
                    $data['action_id'] = $log->action_id;
                    $data['approval_signature'] = $log->approval_signature;
                    $data['final_approval_date'] = $log->final_approval_date;
                    $data['manager_type_id'] = $manager_type_id;
                    $data['levels'] = $level;
                    $data['submitted_by'] = $entered_by;
                    $data['proxy'] = $log->proxy;
                    $data['rate'] = $log->rate;
                    $data['payment_type_id'] = $log->payment_type_id;
                    $data['expected_hours'] = $log->expected_hours;
                    $data['min_hours'] = $log->min_hours;
                    $data['partial_hours'] = $log->partial_hours;
                    $data['log_hours'] = $log->log_hours;
                    $data['action_category'] = $log->action_category;
                    $result[] = $data;

                    // Below code is used for getting sorted log data based on payment_type.
                    $sorted_result[$log->payment_type_id][] = $data;
                }
            } else {
            }
        }
        $responceResult['items'] = $result;
        $responceResult['logs'] = $logs;
        $responceResult['sorted_logs_data'] = $sorted_result;
        //$responceResult['dates']=["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
//        if ($report) {
//            return ["start" => $temp_start_date, "end" => $temp_end_date];
//        }
        return $responceResult;
    }

    // Function to fetch level One data.
    public static function SummationDataLevelOne($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $report = false)
    {
        $responceResult = array();
        $result = array();
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //$contracts = self::getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        $agreement_ids = ApprovalManagerInfo::select("agreement_id")
            ->where("is_deleted", "=", '0')
            //->where("user_id", "=", $user_id)
            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->pluck("agreement_id");
        $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
            ->where("contracts.default_to_agreement", "=", "1")
            ->whereNull("contracts.deleted_at")
            ->whereIN("contracts.agreement_id", $agreement_ids);
        $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
            ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
            ->where("agreement_approval_managers_info.contract_id", ">", 0)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->whereNull("contracts.deleted_at")
            //->where("user_id", "=", $user_id)
            ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

        $ratesArray = ContractRate::contractRates($contracts_for_user);
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $prior_month_end_date = '0000-00-00';
        foreach ($contracts_for_user as $key => $contract_id) {
            $contract_obj = Contract::find($contract_id);
            if ($contract_obj) {
                $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
                if ($agreement_data) {
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                    if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                        $prior_month_end_date = $res_pay_frequency['prior_date'];
                    }
                } else {
                    $contracts_for_user->forget($key);
                    break;
                }
            } else {
                $contracts_for_user->forget($key);
                break;
            }
        }

        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");

        $dates = self::datesForApprovalDashboard($user_id, $contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, '', '');
        if ($start_date == '' && $end_date == '') {
            // Log::info("Summation data level 1 start date and end date before setting values",array($start_date,$end_date));
            $start_date = $dates->temp_start_date;
            $end_date = $dates->temp_end_date;
        }
        $LevelOne = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("contract_types.name, contract_types.id"),
            DB::raw("contracts.payment_type_id, contracts.wrvu_payments"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("MIN(physician_logs.date) as log_min_date"),
            DB::raw("hospitals.id as hospital_id"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("MAX(physician_logs.date) as log_max_date"),
            DB::raw("SUM(physician_logs.duration) as total_duration"),
            DB::raw("ROUND(SUM(physician_logs.duration * contracts.rate)) as total_payment"),
            DB::raw("COUNT(physician_logs.contract_id) as total_contracts_to_approve"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id)
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            })
            ->where(function ($query1) use ($user_id) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->whereIN("physician_logs.contract_id", $contracts_for_user);

        if ($start_date != '' && $end_date != '') {
            $LevelOne = $LevelOne->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $LevelOne = $LevelOne->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        // check if payment_type scrollDown selected. If yes filter the logs
        if ($payment_type != 0) {
            $LevelOne = $LevelOne->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $LevelOne = $LevelOne->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $LevelOne = $LevelOne->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $LevelOne = $LevelOne->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $LevelOne = $LevelOne->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $LevelOne = $LevelOne->where('physician_logs.physician_id', '=', $physician_id);
        }
        $LevelOne->where('agreements.archived', '=', false);
        $LevelOne->groupBy('contract_types.id');
        $LevelOne->orderBy('physician_logs.date', 'ASC');
        $LevelOne = $LevelOne->get();
        // $LevelOne['dates']=["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        // $responceResult['LevelOne']=$LevelOne;

        // Below condition is added for checking the Approval selected and calculating the payment.
        foreach ($LevelOne as $key => $levelOneObj) {

            // Below code is used for date format conversion.
            $time_min = strtotime($levelOneObj->log_min_date);
            $period_min = date("M", $time_min) . ' ' . date("Y", $time_min);

            $time_max = strtotime($levelOneObj->log_max_date);
            $period_max = date("M", $time_max) . ' ' . date("Y", $time_max);

            $levelOneObj->period_min = $period_min;
            $levelOneObj->period_max = $period_max;
            // End of date format conversion.

            $contract_type_id = $levelOneObj->id;
            $levelOneObj->flagApprove = false;

            $levelTwoCount = self::SummationDataLevelTwo($user_id, $type, $payment_type, $levelOneObj->id, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date, $end_date, $report = true);
//            Log::info('Level two data: ', $levelTwoCount);
            $contract_ready_to_approve = 0;
            $calculated_total_payment = 0;
            $processing_contract_id = 0;
            $calculated_payment = 0.00;
            foreach ($levelTwoCount['LevelTwo'] as $levelTwoObj) {
                // $calculated_total_payment += $levelTwoObj->calculated_payment;

                $logs = self::LogsForApprover($user_id, $type, $payment_type, $contract_type_id, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date, $end_date, $agreement_ids, $contracts_for_user, $report = true, $levelTwoObj->contract_id, $levelTwoObj->contract_name_id, true);

//                log::info('temp_log===>', array($logs));

                if (count($logs['sorted_logs_data']) > 0) {
                    $payment_type_factory = new PaymentTypeFactoryClass();

                    foreach ($logs['sorted_logs_data'] as $payment_type_id => $logs) {
                        $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($payment_type_id);
                        $result = $payment_type_obj->calculatePayment($logs, $ratesArray);
                        $calculated_payment = $calculated_payment + ($result['calculated_payment'] != null ? str_replace(',', '', $result['calculated_payment']) : 0.00);
                    }
                    $levelOneObj->calculated_payment = number_format($calculated_payment, 2);
                    $levelOneObj->approval_log_ids = $result['log_ids'];
                    $levelOneObj->flagApprove = $result['flagApprove'];
                }

                // if($processing_contract_id != $levelTwoObj->contract_id){
                $calculated_total_payment += str_replace(',', '', $levelTwoObj->calculated_payment);
                // }
                if ($levelTwoObj->flagApprove == true) {
                    // if($processing_contract_id != $levelTwoObj->contract_id){
                    $contract_ready_to_approve += 1;
                    // }
                }
                $processing_contract_id = $levelTwoObj->contract_id;
            }

            $levelTwoTotalCount = $contract_ready_to_approve;
            $levelOneObj->level_two_count = $levelTwoTotalCount;


//            if(count($logs['sorted_logs_data']) > 0){
//                $payment_type_factory = new PaymentTypeFactoryClass();
//
//                foreach ($logs['sorted_logs_data'] as $payment_type_id => $logs){
//                    $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($payment_type_id);
//                    $result = $payment_type_obj->calculatePayment($logs, $ratesArray) ;
//                    $calculated_payment = $calculated_payment + ($result['calculated_payment'] != null ? str_replace(',', '', $result['calculated_payment']) : 0.00);
//                }
//                $levelOneObj->calculated_payment = number_format($calculated_payment,2);
//                $levelOneObj->approval_log_ids = $result['log_ids'];
//                $levelOneObj->flagApprove = $result['flagApprove'];
//
//
//                $levelOneObj->calculated_payment = number_format($calculated_total_payment, 2);
//                $levelTwoTotalCount = $contract_ready_to_approve;
//                $levelOneObj->level_two_count = $levelTwoTotalCount;
//            } else {
//                $LevelOne->forget($key); // Remove the current object from the collection if logs not found.
//            }
        }
        // log::info('$LevelOne===>', array($LevelOne));
        $responceResult['LevelOne'] = $LevelOne;
        $responceResult['dates'] = ["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        return $responceResult;
    }

    // Function to get level two data.
    public static function SummationDataLevelTwo($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $report = false)
    {
        $responceResult = array();
        $result = array();
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //$contracts = self::getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        $agreement_ids = ApprovalManagerInfo::select("agreement_id")
            ->where("is_deleted", "=", '0')
            //->where("user_id", "=", $user_id)
            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->pluck("agreement_id");
        $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
            ->where("contracts.default_to_agreement", "=", "1")
            ->whereNull("contracts.deleted_at")
            ->whereIN("contracts.agreement_id", $agreement_ids);
        $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
            ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
            ->where("agreement_approval_managers_info.contract_id", ">", 0)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->whereNull("contracts.deleted_at")
            //->where("user_id", "=", $user_id)
            ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

        $ratesArray = ContractRate::contractRates($contracts_for_user);
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_end_date = '0000-00-00';;
        foreach ($contracts_for_user as $key => $contract_id) {
            $contract_obj = Contract::find($contract_id);
            if ($contract_obj) {
                $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
                if ($agreement_data) {
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                    if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                        $prior_month_end_date = $res_pay_frequency['prior_date'];
                    }
                } else {
                    $contracts_for_user->forget($key);
                    break;
                }
            } else {
                $contracts_for_user->forget($key);
                break;
            }
        }
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");

        //$dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');

        $LevelTwo = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("physicians.id as physician_id, CONCAT(physicians.first_name , ' ' , physicians.last_name ) as physician_name"),
            DB::raw("COUNT(physician_logs.contract_id) as total_contracts_to_approve, SUM(physician_logs.duration) AS hours_to_approve"),
            DB::raw("contract_names.name"),
            DB::raw("contract_names.id as contract_name_id"),
            DB::raw("MIN(physician_logs.date) as log_min_date"),
            DB::raw("MAX(physician_logs.date) as log_max_date"),
            DB::raw("contracts.payment_type_id, contracts.wrvu_payments"),
            DB::raw("contracts.expected_hours as total_expected_hours"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.allow_max_hours as allow_max_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.annual_cap as annual_max_hours"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("contracts.rate as fmv_rate"),
            DB::raw("ROUND(SUM(physician_logs.duration * contracts.rate)) AS calculated_payment"),
            DB::raw("contract_types.id as contract_type_id"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id)
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            })
            ->where(function ($query1) use ($user_id) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->whereIN("physician_logs.contract_id", $contracts_for_user);

        if ($start_date != '' && $end_date != '') {
            $LevelTwo = $LevelTwo->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $LevelTwo = $LevelTwo->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        // check if payment_type scrollDown selected. If yes filter the logs
        if ($payment_type != 0) {
            $LevelTwo = $LevelTwo->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $LevelTwo = $LevelTwo->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $LevelTwo = $LevelTwo->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $LevelTwo = $LevelTwo->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $LevelTwo = $LevelTwo->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $LevelTwo = $LevelTwo->where('physician_logs.physician_id', '=', $physician_id);
        }
        $LevelTwo->where('agreements.archived', '=', false);
        $LevelTwo->where('contract_types.id', '=', $contract_type);
        // $LevelTwo->groupBy('contracts.payment_type_id');
        $LevelTwo->groupBy('contracts.id');
        // $LevelTwo->groupBy('physician_logs.physician_id');
        $LevelTwo->orderBy('physicians.first_name', 'ASC');
        $LevelTwo = $LevelTwo->get();

        // Below condition is added for checking the Approval selected and payment calculations.
        foreach ($LevelTwo as $key => $level_two_obj) {
            $level_two_obj->flagApprove = false;
            $level_two_obj->flagReject = true;

            $logs = self::LogsForApprover($user_id, $type, $level_two_obj->payment_type_id, $level_two_obj->contract_type_id, $level_two_obj->hospital_id, $level_two_obj->agreement_id, $level_two_obj->practice_id, $physician_id, $start_date, $end_date, $agreement_ids, $contracts_for_user, $report = true, $level_two_obj->contract_id, $level_two_obj->contract_name_id, true);
            /**
             * Below code is used for gettting calculation from the respective payment type class for logs object.
             */

            $payment_type_factory = new PaymentTypeFactoryClass(); // This is a factory class used for returning the required payment type object of class based on parameter payment type.
            // log::info('$logsLevel2', array($logs));
            if (count($logs['sorted_logs_data']) > 0) {
                foreach ($logs['sorted_logs_data'] as $payment_type_id => $logs) {
                    $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($payment_type_id); // This line is returns the object for calculation based on payment type.
                    $result = $payment_type_obj->calculatePayment($logs, $ratesArray);
                }
                Log::info('result', array($result));
                if($result) {
                    if ($level_two_obj->payment_type_id != PaymentType::PER_DIEM && $level_two_obj->payment_type_id != PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        if ($result && array_key_exists('unique_date_range_arr', $result)) {
                            $calculated_expected_hours = number_format(count($result['unique_date_range_arr']) * $level_two_obj->total_expected_hours, 2);
                        } else {
                            $calculated_expected_hours = 0;
                        }

                        if ($level_two_obj->payment_type_id == PaymentType::STIPEND) {
                            $calculated_payment = $result['calculated_payment'];
                        }
                    } else {
                        $calculated_expected_hours = 0;
                    }
                    $level_two_obj['logs'] = $logs;
                    // $level_two_obj->calculated_payment = number_format($result['calculated_payment'],2); //This line is commented because of number format issue by akash.
                    $level_two_obj->calculated_payment = number_format($result['calculated_payment'], 2);
                    $level_two_obj->hours_to_approve = number_format($result['hours'], 2);
                    // $level_two_obj->expected_hours = number_format($calculated_expected_hours,2);
                    $level_two_obj->expected_hours = number_format(str_replace(",", "", $calculated_expected_hours), 2);   //added for remove comma
                    $level_two_obj->expected_payment = number_format($result['expected_payment'], 2);
                    $level_two_obj->flagApprove = $result['flagApprove'];
                    $level_two_obj->flagReject = $result['flagReject'];
                    //Support only single contract
                    //$contract_document = ContractDocuments::select("filename")->where("contract_id", "=", $level_two_obj->contract_id)->where("is_active", "=", '1')->first();
                    $contract_document = ContractDocuments::select("filename")->where("contract_id", "=", $level_two_obj->contract_id)->where("is_active", "=", '1')->get();


                    $contract_document = (!is_null($contract_document)) ? $contract_document : "NA";


                    $level_two_obj->contract_document = $contract_document;

                    $time_min = strtotime($level_two_obj->log_min_date);
                    $period_min = date("M", $time_min) . ' ' . date("Y", $time_min);

                    $time_max = strtotime($level_two_obj->log_max_date);
                    $period_max = date("M", $time_max) . ' ' . date("Y", $time_max);

                    $level_two_obj->period_min = $period_min;
                    $level_two_obj->period_max = $period_max;
                }
            } else {
                $LevelTwo->forget($key); // Remove the current object from the collection if logs not found.
            }
        }

        // $LevelOne['dates']=["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        $responceResult['LevelTwo'] = $LevelTwo;
        return $responceResult;
    }

    public static function PaymentStatusLevelOne($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $check_status = 0, $report = false, $approver = 0)
    {
        $result = array();
        $practiceArray = array();
        if (Auth::check()) {
            if (is_practice_manager()) {
                $practice = DB::table('practice_user')
                    ->where('user_id', '=', $user_id)
                    ->select('practice_id')->get();
                foreach ($practice as $practiceID) {
                    $practiceArray[] = $practiceID->practice_id;
                }
            }
        }
        if (count($practiceArray) == 0) {
            $practiceArray[] = 0;
        }
        $contracts = DB::table('contracts')->select(
            DB::raw("contracts.id"))
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id');
        if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $contracts = $contracts->join('practices', 'practices.hospital_id', '=', 'agreements.hospital_id');
            $contracts = $contracts->join('practice_user', 'practice_user.practice_id', '=', 'practices.id');
        } else {
            $contracts = $contracts->join('hospital_user', 'hospital_user.hospital_id', '=', 'agreements.hospital_id');
        }
        $contracts = $contracts->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id");
        $contracts = $contracts->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id');
        if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $contracts = $contracts->where('practice_user.user_id', '=', $user_id);
        } else {
            $contracts = $contracts->where('hospital_user.user_id', '=', $user_id);
        }
        if ($payment_type != 0) {
            $contracts = $contracts->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $contracts = $contracts->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $contracts = $contracts->where('agreements.hospital_id', '=', $hospital_id);
        }
        if (Auth::check()) {
            if (is_practice_manager()) {
                $contracts = $contracts->whereIn('physician_practice_history.practice_id', $practiceArray)
                    ->whereIn('physician_contracts.practice_id', $practiceArray);
            }
        }

        if ($agreement_id != 0) {
            $contracts = $contracts->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $contracts = $contracts->where('physician_practice_history.practice_id', '=', $practice_id)
                ->where('physician_contracts.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $contracts = $contracts->where('physician_contracts.physician_id', '=', $physician_id);
        }

        if ($check_status == 4 && $approver > 0) {
            $proxy_check_id = self::find_proxy_aaprovers($approver);

            $agreement_ids = ApprovalManagerInfo::select("agreement_id")
                ->where("is_deleted", "=", '0')
                ->whereIn("user_id", $proxy_check_id)
                ->pluck("agreement_id");

            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);

            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->whereNull("contracts.deleted_at")
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id)
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

            $contracts = $contracts->whereIn('contracts.id', $contracts_for_user);
        }

        //$contracts = $contracts->orderBy('contracts.contract_name_id')->orderBy('physician_practice_history.practice_id')->get();
        $contracts = $contracts->where('agreements.archived', '=', false)->orderBy('physician_practice_history.physician_id')
            ->orderBy('physician_practice_history.practice_id')->orderBy('contract_names.name')->distinct()->pluck("contracts.id");
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");
        if (count($contracts) > 0) {
            $contracts_string = implode(',', $contracts->toArray());
        } else {
            $contracts[] = 0;
            $contracts_string = 0;
        }

        if ($contracts_string > 0) {
            $prior_month_end_date = "0000-00-00";
            foreach ($contracts as $contract) {
                $contract_obj = Contract::withTrashed()->findOrFail($contract);
                $agreement_details = Agreement::withTrashed()->findOrFail($contract_obj->agreement_id);
                $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
                $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
                $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement_details);

                if ($prior_month_end_date <= $res_pay_frequency['prior_date'] && $res_pay_frequency['prior_date'] < date('Y-m-d', strtotime(now()))) {
                    $prior_month_end_date = $res_pay_frequency['prior_date'];
                }
            }

            $payment_status_level_one = PaymentStatusDashboard::select(
                'payment_status_dashboard.physician_id', 'payment_status_dashboard.practice_id', 'practices.name as practice_name',
                DB::raw('CONCAT(physicians.last_name, " ", physicians.first_name) AS full_name'),
                DB::raw('payment_status_dashboard.period_min_date as period_min_date'),
                DB::raw('payment_status_dashboard.period_max_date as period_max_date')
            )
                ->whereIn('payment_status_dashboard.contract_id', $contracts->toArray())
                ->join("physicians", "physicians.id", "=", "payment_status_dashboard.physician_id")
                ->join("practices", "practices.id", "=", "payment_status_dashboard.practice_id")
                ->join("contract_names", "contract_names.id", "=", "payment_status_dashboard.contract_name_id")
                ->where('payment_status_dashboard.period_max_date', '<=', $prior_month_end_date);

            if ($check_status != 0) {
                if ($check_status == 1) {
                    $payment_status_level_one->where('payment_status_dashboard.pending_logs', '=', "1")
                        ->where('payment_status_dashboard.pending_logs_hours_approving', '>', 0);
                } else if ($check_status == 2) {
                    $payment_status_level_one->where('payment_status_dashboard.approved_logs', '=', "1");
                } else if ($check_status == 3) {
                    $payment_status_level_one->where('payment_status_dashboard.rejected_logs', '=', "1");
                }
            }

            if ($start_date != '' && $end_date != '') {
                $payment_status_level_one->whereBetween('payment_status_dashboard.period_min_date', [mysql_date($start_date), mysql_date($end_date)]);
            }
            if ($hospital_id != 0) {
                $payment_status_level_one->where('payment_status_dashboard.hospital_id', '=', $hospital_id);
            }
            if ($practice_id != 0) {
                $payment_status_level_one->where('payment_status_dashboard.practice_id', '=', $practice_id);
            }
            if ($physician_id != 0) {
                $payment_status_level_one->where('payment_status_dashboard.physician_id', '=', $physician_id);
            }

            if ($start_date != '' && $end_date != '') {
                $payment_status_level_one->whereBetween('payment_status_dashboard.period_min_date', [mysql_date($start_date), mysql_date($end_date)]);
            }

            // $payment_status_level_one = $payment_status_level_one->groupBy('payment_status_dashboard.practice_id')->get();
            $payment_status_level_one = $payment_status_level_one->get();

            foreach ($payment_status_level_one as $key => $level_one_obj) {
                $payment_status_level_two_obj = self::PaymentStatusLevelTwo($level_one_obj->physician_id, $level_one_obj->practice_id, $level_one_obj->period_min_date, $level_one_obj->period_max_date, $check_status, $payment_type, $contract_type, $hospital_id, $agreement_id, $approver);
                if (count($payment_status_level_two_obj) > 0) {
                    $level_one_obj->level_two_count = count($payment_status_level_two_obj);
                    $level_one_obj->period_min_date = $payment_status_level_two_obj->min('period_min_date');
                    $level_one_obj->period_max_date = $payment_status_level_two_obj->max('period_max_date');

                    $record_exists = $payment_status_level_one->where('physician_id', $level_one_obj->physician_id)
                        ->where('practice_id', $level_one_obj->practice_id)
                        ->where('period_min_date', $level_one_obj->period_min_date)
                        ->where('period_max_date', $level_one_obj->period_max_date)
                        ->count();

                    if ($record_exists > 1) {
                        $payment_status_level_one->forget($key);
                    }
                } else {
                    $payment_status_level_one->forget($key);
                }
            }

            return $payment_status_level_one;
        } else {
            return [];
        }
    }

    public static function PaymentStatusLevelTwo($physician_id, $practice_id, $period_min_date, $period_max_date, $check_status, $payment_type, $contract_type, $hospital_id, $agreement_id, $approver)
    {

        $data = array();
        $payment_status_level_two = PaymentStatusDashboard::select(
            'payment_status_dashboard.physician_id', 'payment_status_dashboard.practice_id',
            'contract_names.name as contract_name', 'contracts.payment_type_id', 'contracts.id as contract_id',
            'payment_status_dashboard.hours_approving',
            'payment_status_dashboard.approved_logs_hours_approving',
            'payment_status_dashboard.pending_logs_hours_approving',
            'payment_status_dashboard.rejected_logs_hours_approving',
            'payment_status_dashboard.expected_hours',
            'payment_status_dashboard.period_min_date',
            'payment_status_dashboard.period_max_date'
        )
            ->join("contract_names", "contract_names.id", "=", "payment_status_dashboard.contract_name_id")
            ->join("contracts", "contracts.id", "=", "payment_status_dashboard.contract_id")
            ->where('payment_status_dashboard.physician_id', '=', $physician_id)
            ->where('payment_status_dashboard.practice_id', '=', $practice_id);

        if ($check_status != 0) {
            if ($check_status == 1) {
                $payment_status_level_two->where('payment_status_dashboard.pending_logs', '=', "1");
            } else if ($check_status == 2) {
                $payment_status_level_two->where('payment_status_dashboard.approved_logs', '=', "1");

            } else if ($check_status == 3) {
                $payment_status_level_two->where('payment_status_dashboard.rejected_logs', '=', "1");
            }
        }

        if ($period_min_date != '' && $period_max_date != '') {
            // $payment_status_level_two = $payment_status_level_two->whereBetween('payment_status_dashboard.period_min_date', [mysql_date($period_min_date), mysql_date($period_max_date)]);
            $payment_status_level_two->where('payment_status_dashboard.period_min_date', '>=', $period_min_date)
                ->where('payment_status_dashboard.period_max_date', '<=', $period_max_date);
        }

        if ($payment_type != 0) {
            $payment_status_level_two = $payment_status_level_two->where('contracts.payment_type_id', '=', $payment_type);
        }

        if ($contract_type != 0) {
            $payment_status_level_two = $payment_status_level_two->where('contracts.contract_type_id', '=', $contract_type);
        }

        if ($hospital_id != 0) {
            $payment_status_level_two = $payment_status_level_two->where('payment_status_dashboard.hospital_id', '=', $hospital_id);
        }

        if ($agreement_id != 0) {
            $payment_status_level_two = $payment_status_level_two->where('contracts.agreement_id', '=', $agreement_id);
        }
        // if ($practice_id != 0) {
        //     $payment_status_level_two = $payment_status_level_two->where('physician_practice_history.practice_id', '=', $practice_id);
        // }
        // if ($physician_id != 0) {
        //     $payment_status_level_two = $payment_status_level_two->where('contracts.physician_id', '=', $physician_id);
        // }

        $payment_status_level_two = $payment_status_level_two->get();

        foreach ($payment_status_level_two as $key => $level_two_obj) {
            /**
             * This block of code is added for checking the full amount paid for that contract fot that period logs if not then fetch those logs as pending payment.
             */
            // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
            $logs_for_calculation = PhysicianLog::select(
                DB::raw("physician_logs.date as log_date"),
                DB::raw("physician_logs.duration as duration"),
                DB::raw("physician_logs.id as log_id"),
                DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
                DB::raw("actions.name as action"),
                DB::raw("physician_logs.practice_id as practice_id"),
                DB::raw("physician_logs.contract_id as contract_id"),
                DB::raw("physician_logs.physician_id as physician_id"),
                DB::raw("physician_logs.log_hours"),
                DB::raw("contracts.partial_hours as partial_hours"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.min_hours as min_hours"),
                DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
                DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contracts.agreement_id as agreement_id"),
                DB::raw("physician_logs.approval_date as approval_date"),
                DB::raw("physician_logs.signature as signature"),
                DB::raw("IF(physician_logs.physician_id = " . $level_two_obj->physician_id . ", 'Waiting', 'NA') as current_user_status")
            )
                ->distinct('physician_logs.id')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
//                ->where("physician_logs.signature", "!=", 0)
                ->whereNull("physician_logs.deleted_at")
                // ->where("physician_logs.approval_date", "!=", "0000-00-00")
                ->where("physician_logs.practice_id", '=', $level_two_obj->practice_id)
                ->where("physician_logs.physician_id", '=', $level_two_obj->physician_id)
                ->where("physician_logs.contract_id", '=', $level_two_obj->contract_id);

            if ($check_status == 4 && $approver > 0) {
                $logs_for_calculation = $logs_for_calculation->where("physician_logs.next_approver_user", "=", $approver);
            }

            if ($check_status != 0) {
                if ($check_status == 1) {
                    $logs_for_calculation = $logs_for_calculation->where("physician_logs.approval_date", "=", "0000-00-00")
                        ->where("physician_logs.next_approver_level", "=", 0)
                        ->where("physician_logs.next_approver_user", "=", 0);
                } else if ($check_status == 2) {
                    $logs_for_calculation = $logs_for_calculation->where("physician_logs.approval_date", "!=", "0000-00-00")
                        ->where("physician_logs.signature", "!=", "0000-00-00");
                } else if ($check_status == 3) {
                    $logs_for_calculation = $logs_for_calculation->where("physician_logs.approval_date", "=", "0000-00-00");
                }
            }

            $logs_for_calculation = $logs_for_calculation->whereBetween('physician_logs.date', [mysql_date($level_two_obj->period_min_date), mysql_date($level_two_obj->period_max_date)])
                ->get();

            $temp_logs_for_calculation = collect($logs_for_calculation);
            $temp_logs_in_process_or_rejected = collect($logs_for_calculation);

            $logs_for_calculation_approved = $temp_logs_for_calculation->where("signature", "!=", 0)
                ->where("approval_date", "!=", "0000-00-00")->values();

            $logs_in_process_or_rejected_count = $temp_logs_in_process_or_rejected->where("approval_date", "=", "0000-00-00")->count();

            if ($check_status == 4 && $approver > 0) {
                $approvers_pending_duration = $temp_logs_in_process_or_rejected->where("approval_date", "=", "0000-00-00")->sum('duration');
                $level_two_obj->hours_approving = $approvers_pending_duration;
            }

            $payment_type_factory = new PaymentTypeFactoryClass();

            $contract_obj = Contract::where('id', '=', $level_two_obj->contract_id)->first();
            $ratesArray = ContractRate::contractRates([$level_two_obj->contract_id]);

            if (count($logs_for_calculation_approved) > 0 && $logs_in_process_or_rejected_count == 0) {
                $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($contract_obj->payment_type_id); // This line is returns the object for calculation based on payment type.
                $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation_approved, $ratesArray);
//            log::info('$calculation_result', array($calculation_result));
                $calculated_payment = $calculation_result['calculated_payment'];

                if ($calculated_payment <= 0) {
                    $payment_status_level_two->forget($key);
                } else {
                    /* $get_amount_paid = Amount_paid::where('start_date', '=', mysql_date($level_two_obj->period_min_date))
                        ->where('end_date', '=', mysql_date($level_two_obj->period_max_date))
                        ->where('physician_id', '=', $level_two_obj->physician_id)
                        ->where('contract_id', '=', $level_two_obj->contract_id)
                        ->where('practice_id', '=', $level_two_obj->practice_id)
                        ->get(); */

                    $get_amount_paid = Amount_paid::select('amount_paid.*')
                        ->join('amount_paid_physicians', 'amount_paid_physicians.amt_paid_id', '=', 'amount_paid.id')
                        ->where('amount_paid.start_date', '=', mysql_date($level_two_obj->period_min_date))
                        ->where('amount_paid.end_date', '=', mysql_date($level_two_obj->period_max_date))
                        ->where('amount_paid_physicians.physician_id', '=', $level_two_obj->physician_id)
                        ->where('amount_paid.contract_id', '=', $level_two_obj->contract_id)
                        ->where('amount_paid.practice_id', '=', $level_two_obj->practice_id)
                        ->get();

                    if (count($get_amount_paid) > 0) {

                        $check_final_payment_flag = false;
                        foreach ($get_amount_paid as $paid_amount_obj) {
                            if ($paid_amount_obj->final_payment == 1) {
                                $payment_status_level_two->forget($key);
                                $check_final_payment_flag = true;
                            }
                        }

                        if ($check_final_payment_flag == false) {
                            $amount_paid = $get_amount_paid->sum('amountPaid');
                            /**
                             * If the amount paid and calculated payment is same then do not fetch the record other-wise fetch the record and display as pending payment.
                             **/

                            //Amount is paid to two precision level only so to comparision is also done to two decimal precision.
                            $amount_paid = round($amount_paid, 2);
                            $calculated_payment = round($calculated_payment, 2);

                            if ($amount_paid >= $calculated_payment) {
                                $payment_status_level_two->forget($key);
                            }
                        }
                    }
                }
            } else if (count($logs_for_calculation) == 0) {
                $payment_status_level_two->forget($key);
            }

            // Full amount paid check block ends here.
        }
//        log::info('$payment_status_level_two', array($payment_status_level_two));
//        if(count($payment_status_level_two) > 0){
        return $payment_status_level_two;
//        }
    }

    public static function LogsApproverstatus($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $check_status = 0, $report = false, $contract_id, $approver)
    {
        $result = array();
//        $contracts_old = self::getContracts($user_id,$type,$contract_type,$hospital_id, $agreement_id,$practice_id,$physician_id);
        $practiceArray = array();
        if (Auth::check()) {
            if (is_practice_manager()) {
                $practice = DB::table('practice_user')
                    ->where('user_id', '=', $user_id)
                    ->select('practice_id')->get();
                foreach ($practice as $practiceID) {
                    $practiceArray[] = $practiceID->practice_id;
                }
            }
        }
        if (count($practiceArray) == 0) {
            $practiceArray[] = 0;
        }
        $contracts = DB::table('contracts')->select(
            DB::raw("contracts.id"))
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id');
        if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $contracts = $contracts->join('practices', 'practices.hospital_id', '=', 'agreements.hospital_id');
            $contracts = $contracts->join('practice_user', 'practice_user.practice_id', '=', 'practices.id');
        } else {
            $contracts = $contracts->join('hospital_user', 'hospital_user.hospital_id', '=', 'agreements.hospital_id');
        }
        $contracts = $contracts->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id");
        $contracts = $contracts->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id');
        if ($type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $contracts = $contracts->where('practice_user.user_id', '=', $user_id);
        } else {
            $contracts = $contracts->where('hospital_user.user_id', '=', $user_id);
        }
        if ($payment_type != 0) {
            $contracts = $contracts->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $contracts = $contracts->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $contracts = $contracts->where('agreements.hospital_id', '=', $hospital_id);
        }
        if (Auth::check()) {
            if (is_practice_manager()) {
                $contracts = $contracts->whereIn('physician_practice_history.practice_id', $practiceArray);
            }
        }

        if ($agreement_id != 0) {
            $contracts = $contracts->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $contracts = $contracts->where('physician_practice_history.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $contracts = $contracts->where('physician_contracts.physician_id', '=', $physician_id);
        }
        if ($contract_id != 0) {
            $contracts = $contracts->where('contracts.id', '=', $contract_id);
        }

        if ($check_status == 4 && $approver > 0) {
            $proxy_check_id = self::find_proxy_aaprovers($approver);

            $agreement_ids = ApprovalManagerInfo::select("agreement_id")
                ->where("is_deleted", "=", '0')
                ->whereIn("user_id", $proxy_check_id)
                ->pluck("agreement_id");

            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);

            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->whereNull("contracts.deleted_at")
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id)
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

            $contracts = $contracts->whereIn('contracts.id', $contracts_for_user);
        }

        //$contracts = $contracts->orderBy('contracts.contract_name_id')->orderBy('physician_practice_history.practice_id')->get();
        $contracts = $contracts->where('agreements.archived', '=', false)->orderBy('physician_practice_history.physician_id')
            ->orderBy('physician_practice_history.practice_id')->orderBy('contract_names.name')->distinct()->pluck("contracts.id");
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");
        if (count($contracts) > 0) {
            $contracts_string = implode(',', $contracts->toArray());
        } else {
            $contracts[] = 0;
            $contracts_string = 0;
        }
        $dates = self::datesForPaymentStatusDashboard($user_id, $contracts, $contracts_string, $practiceArray, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, '', '', $check_status);
        $logs = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("if(contracts.payment_type_id=2,1,0) as payment_type_sort"),
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.details as log_details"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("physician_logs.signature as approval_signature"),
            DB::raw("physician_logs.approval_date as final_approval_date"),
            DB::raw("physician_logs.entered_by as entered_by"),
            DB::raw("physician_logs.entered_by_user_type as entered_by_user_type"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.next_approver_level as next_approver_level"),
            DB::raw("physician_logs.next_approver_user as next_approver_user"),
            DB::raw("physicians.first_name as physician_first_name"),
            DB::raw("physicians.last_name as physician_last_name"),
            DB::raw("contracts.default_to_agreement as default_to_agreement"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id to apply filter
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("hospitals.name as hospital_name"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->whereIN("physician_logs.contract_id", $contracts);
        // ->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` INNER JOIN `amount_paid` ON
        //             `physician_logs`.`contract_id`=`amount_paid`.`contract_id` AND `physician_logs`.`date` >= `amount_paid`.`start_date` AND
        //              `physician_logs`.`date` <= `amount_paid`.`end_date` WHERE `physician_logs`.`contract_id` IN(".$contracts_string.") AND
        //               `amount_paid`.`contract_id` IN(".$contracts_string.") and physician_logs.deleted_at is null and physician_logs.approved_by !=0 and physician_logs.approving_user_type != 0)");
        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        } else if (is_practice_manager()) {
            $logs = $logs->whereIn('physician_logs.practice_id', $practiceArray);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('physician_logs.physician_id', '=', $physician_id);
        }
        if ($check_status == 2) {
            $logs = $logs->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00");
        } elseif ($check_status != 0) {
            $logs = $logs->where("physician_logs.signature", "=", 0)
                ->where("physician_logs.approval_date", "=", "0000-00-00");
            if ($check_status == 3) {
                $logs = $logs->whereRaw("physician_logs.id IN (SELECT log_id FROM `log_approval_history` WHERE id IN (SELECT MAX(id) FROM `log_approval_history` GROUP BY log_id) AND approval_status=0)");
            } else if ($check_status == 4 && $approver > 0) {
                $logs = $logs->where("physician_logs.next_approver_user", '=', $approver);
            }
        }
        /*add condition for remove approved logs appear more than 120 days*/ // Below two lines commented by akash.
        // $logs = $logs->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` WHERE `physician_logs`.`contract_id` IN(".$contracts_string.") AND
        //                   `physician_logs`.`signature` != 0 AND `physician_logs`.`approval_date` != '0000-00-00' AND DATE_ADD(LAST_DAY(date),INTERVAL 120 DAY) < date '".date('Y-m-d')."' )");

        /*$logs = $logs->where(function ($query) {
            $query->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00")
                ->whereRaw("DATE_ADD(LAST_DAY(date),INTERVAL 120 DAY) > date '".date('Y-m-d')."'");
        });*/

        /**
         * This block of code is added for checking the full amount paid for that contract fot that period logs if not then fetch those logs as pending payment.
         */
        // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
        $logs_for_calculation = PhysicianLog::select(
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.log_hours"),
            DB::raw("contracts.partial_hours as partial_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("'Waiting' as current_user_status")
        )
            ->distinct('physician_logs.id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "!=", 0)
            ->whereNull("physician_logs.deleted_at")
            ->where("physician_logs.approval_date", "!=", "0000-00-00")
            ->where("physician_logs.practice_id", '=', $practice_id)
//            ->where("physician_logs.physician_id", '=', $physician_id)
            ->whereIn("physician_logs.contract_id", $contracts)
            ->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)])
            ->get();

        $payment_type_factory = new PaymentTypeFactoryClass();

        $contract_obj = Contract::where('id', '=', $contracts[0])->first();
        $ratesArray = ContractRate::contractRates($contracts);

        if (count($logs_for_calculation) > 0) {
            $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($contract_obj->payment_type_id); // This line is returns the object for calculation based on payment type.
            $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation, $ratesArray);
//            log::info('$calculation_result', array($calculation_result));
            $calculated_payment = $calculation_result['calculated_payment'];

            $get_amount_paid = Amount_paid::where('start_date', '=', mysql_date($start_date))
                ->where('end_date', '=', mysql_date($end_date))
//                ->where('physician_id', '=', $physician_id)
                ->whereIn('contract_id', $contracts)
                ->where('practice_id', '=', $practice_id)
                ->get();

            if (count($get_amount_paid) > 0) {
                $amount_paid = $get_amount_paid->sum('amountPaid');
                /**
                 * If the amount paid and calculated payment is same then do not fetch the record other-wise fetch the record and display as pending payment.
                 **/
                if ($amount_paid == $calculated_payment) {
                    $logs->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` INNER JOIN `amount_paid` ON
                                `physician_logs`.`contract_id`=`amount_paid`.`contract_id` AND `physician_logs`.`date` >= `amount_paid`.`start_date` AND
                                `physician_logs`.`date` <= `amount_paid`.`end_date` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                                `amount_paid`.`contract_id` IN(" . $contracts_string . ") and physician_logs.deleted_at is null and physician_logs.approved_by !=0 and physician_logs.approving_user_type != 0)");
                }
            }
        }

        // Full amount paid check block ends here.

        $logs = $logs->where('agreements.archived', '=', false)
            ->whereRaw("physician_logs.contract_id not in (select c.id from contracts c where c.payment_type_id = 4 and c.wrvu_payments = false)")
            ->orderBy('current_user_status_sort', "DESC")
            ->orderBy('payment_type_sort', "DESC")
            ->orderBy('physician_logs.physician_id')
            //drop column practice_id from table 'physicians' changes by 1254
            ->orderBy('physician_logs.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.date')
            ->orderBy('physician_logs.next_approver_user', "DESC");

        if ($report) {
            $logs = $logs->get();
        } else {
            $logs = $logs->paginate(10);
        }
        $prev_contract_id = 0;
//        log::info('LogsApproverstatus', array($logs));
        foreach ($logs as $log) {
            $practice = Practice::withTrashed()->where('id', '=', $log->practice_id)->first();
            if ($practice) {
                $isRejected = false;
                $level = array();
                $logsForApproval = array();
                $logdate = strtotime($log->log_date);
                if ($log->next_approver_user == $user_id) {
                    $log->current_user_status = "Waiting";
                    $level[] = [
                        "status" => "Approved",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                } else {
                    $log->current_user_status = "Pending";
                    $physician_approval_status = "Pending";
                    if ($log->approval_signature != 0 || $log->final_approval_date != '0000-00-00') {
                        $physician_approval_status = "Approved";
                    } else {
                        $log_approval_physician = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", "=", 0)->orderBy("approval_managers_level")->get();
                        if (count($log_approval_physician) > 0) {
                            $physician_approval_status = "Approved";
                        }
                    }
                    $level[] = [
                        "status" => $physician_approval_status,
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                }
                if ($prev_contract_id != $log->contract_id) {
                    $approval_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                        ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                        ->where("is_deleted", "=", '0')->orderBy("level")->get();
                    $prev_contract_id = $log->contract_id;
                }
                $log_approval = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", ">", 0)->orderBy("approval_managers_level")->get();
                if (count($log_approval) > 0) {
                    foreach ($log_approval as $approvals) {
                        $user = User::withTrashed()->findOrFail($approvals->user_id);
                        $level[] = [
                            "status" => "Approved",
                            "name" => $user->first_name . ' ' . $user->last_name
                        ];
                    }
                    if (isset($approval_manager_data[$log->next_approver_level - 1])) {
                        $manager_type_id = $approval_manager_data[$log->next_approver_level - 1]->type_id;
                    } else {
                        $manager_type_id = -1;
                    }
                } else {
                    if ($log->next_approver_level > 0) {
                       
                        $approval_current_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                            ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                            ->where("is_deleted", "=", '0')->where("user_id", "=", $log->next_approver_user)->where("level", "=", $log->next_approver_level)->first();
                        $manager_type_id = $approval_current_manager_data->type_id;
                    } else {
                        $manager_type_id = 0;
                    }
                }
                if (count($level) < 7) {
                    for ($la = count($level); $la < 7; $la++) {
                        if (count($approval_manager_data) >= $la) {
                            $user = User::withTrashed()->findOrFail($approval_manager_data[$la - 1]->user_id);
                            $user_name = $user->first_name . ' ' . $user->last_name;
                            $u_status = "Pending";
                            $rejection = DB::table('log_approval_history')->select('*')
                                ->where('log_id', '=', $log->log_id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            if ($rejection) {
                                if ($rejection->approval_status == 0 && $rejection->approval_managers_level == $la) {
                                    $u_status = "Rejected";
                                    $isRejected = true;
                                }
                            }
                        } else {
                            $user_name = " ";
                            $u_status = "N/A";
                        }
                        $level[] = [
                            "status" => $u_status,
                            "name" => $user_name
                        ];
                    }
                }
                if (strtotime($temp_start_date) >= $logdate && strtotime($temp_end_date) > $logdate) {
                    $temp_start_date = $log->log_date;
                } elseif ((strtotime($temp_start_date) < $logdate && strtotime($temp_end_date) <= $logdate) || (strtotime($temp_end_date) == strtotime(date("Y-m-d")) && strtotime($temp_end_date) != $logdate)) {
                    $temp_end_date = $log->log_date;
                }
                $addFlag = false;
                if ($check_status == 1 && !$isRejected) {
                    //$logsForApproval[] = $log;
                    $addFlag = true;
                } elseif ($check_status == 3 && $isRejected) {
                    //$logsForApproval[] = $log;
                    $addFlag = true;
                } elseif ($check_status == 0 || $check_status == 2 || $check_status == 4) {
                    //$logsForApproval[] = $log;
                    $addFlag = true;
                }
                if ($addFlag) {
                    /// CHANGES HERE - if Contract_id and action_id match in
                    // ON CALL cahnge action name
                    //if ($log->contract_type_id == ContractType::ON_CALL){
                    if ($log->payment_type_id == PaymentType::PER_DIEM || $log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $oncallactivity = DB::table('on_call_activities')
                            ->select('name')
                            ->where("contract_id", "=", $log->contract_id)
                            ->where("action_id", "=", $log->action_id)
                            ->first();
                        if ($oncallactivity) {
                            $log->action = $oncallactivity->name;
                        }
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


                    /**
                     * Below line of code is added for checking the amount paid for the logs period.
                     */

                    $payment_status = 'NA';

                    if ($log->final_approval_date != '0000-00-00') {

                        $calculated_payment = 0.00;
                        $amount_paid = 0.00;

                        $agreement_details = Agreement::findOrFail($log->agreement_id);

                        // Get the frequency type for the agreement and then get the periods for that frequency type.
                        $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
                        $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
                        $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement_details);
                        $period_range = $res_pay_frequency['date_range_with_start_end_date'];

                        $period_min = '';
                        $period_max = '';
                        foreach ($period_range as $period) {
                            if (strtotime($log->log_date) >= strtotime($period['start_date']) && strtotime($log->log_date) <= strtotime($period['end_date'])) {
                                $period_min = $period['start_date'];
                                $period_max = $period['end_date'];
                            }
                        }

                        if ($period_min != '' && $period_max != '') {
                            $contracts_arr[] = $log->contract_id;
                            $agreement_ids[] = $log->agreement_id;

                            $ratesArray = ContractRate::contractRates($contracts_arr);

                            // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
                            $logs_for_calculation = PhysicianLog::select(
                                DB::raw("physician_logs.date as log_date"),
                                DB::raw("physician_logs.duration as duration"),
                                DB::raw("physician_logs.id as log_id"),
                                DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
                                DB::raw("actions.name as action"),
                                DB::raw("physician_logs.practice_id as practice_id"),
                                DB::raw("physician_logs.contract_id as contract_id"),
                                DB::raw("physician_logs.physician_id as physician_id"),
                                DB::raw("physician_logs.log_hours"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
                                DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.agreement_id as agreement_id"),
                                DB::raw("IF(physician_logs.physician_id = " . $log->physician_id . ", 'Waiting', 'NA') as current_user_status")
                            )
                                ->distinct('physician_logs.id')
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
                                ->where("physician_logs.signature", "!=", 0)
                                ->whereNull("physician_logs.deleted_at")
                                ->where("physician_logs.approval_date", "!=", "0000-00-00")
                                ->where("contracts.payment_type_id", '=', $log->payment_type_id)
                                ->where("contracts.agreement_id", '=', $log->agreement_id)
                                ->where("physician_logs.practice_id", '=', $log->practice_id)
                                ->where("physician_logs.physician_id", '=', $log->physician_id)
                                ->where("physician_logs.contract_id", '=', $log->contract_id)
                                ->whereBetween('physician_logs.date', [mysql_date($period_min), mysql_date($period_max)])
                                ->get();

                            $payment_type_factory = new PaymentTypeFactoryClass();
                            $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($log->payment_type_id); // This line is returns the object for calculation based on payment type.
                            $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation, $ratesArray);

                            $calculated_payment = $calculation_result['calculated_payment'];

                            $get_amount_paid = Amount_paid::where('start_date', '=', mysql_date($period_min))
                                ->where('end_date', '=', mysql_date($period_max))
                                //                                ->where('physician_id', '=', $log->physician_id)
                                ->where('contract_id', '=', $log->contract_id)
                                ->where('practice_id', '=', $log->practice_id)
                                ->get();

                            if (count($get_amount_paid) > 0) {
                                $amount_paid = $get_amount_paid->sum('amountPaid');
                            }
                            // log::info('$amount_paid', array($amount_paid));
                            // log::info('$calculated_payment', array($calculated_payment));
                            if ($amount_paid < $calculated_payment) {
                                $payment_status = 'Pending';
                            } else {
                                $payment_status = 'Paid'; // This will never show because if the logs are paid then it doesn't show up on the payment status dashboard.
                            }
                        }
                    }
                    // 6.1.15 custom headings as well as actions Start
                    if ($log->payment_type_id == PaymentType::TIME_STUDY) {
                        $custom_action = CustomCategoryActions::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            ->where('action_id', '=', $log->action_id)
                            ->where('action_name', '!=', null)
                            ->where('is_active', '=', true)
                            ->first();

                        $log->action = $custom_action ? $custom_action->action_name : $log->action;
                    }
                    // 6.1.15 custom headings as well as actions End
                    // Payment status check block ends here.

                    /////////////////////////////////////
                    $data['hospital_id'] = $log->hospital_id;
                    $data['hospital_name'] = $log->hospital_name;
                    $data['agreement_name'] = $log->agreement_name;
                    $data['physician_name'] = $log->physician_first_name . ' ' . $log->physician_last_name;
                    $data['contract_name_id'] = $log->contract_name_id;
                    $data['contract_name'] = $log->contract_name;
                    $data['contract_id'] = $log->contract_id;
                    $data['practice_id'] = $log->practice_id;
                    $data['practice_name'] = $practice->name;
                    $data['current_user_status'] = $log->current_user_status;
                    $data['log_date'] = $log->log_date;
                    $data['duration'] = $log->duration;
                    $data['log_details'] = $log->log_details;
                    $data['log_id'] = $log->log_id;
                    $data['action'] = $log->action;
                    $data['approval_signature'] = $log->approval_signature;
                    $data['final_approval_date'] = $log->final_approval_date;
                    $data['manager_type_id'] = $manager_type_id;
                    $data['levels'] = $level;
                    $data['submitted_by'] = $entered_by;
                    $data['payment_status'] = $payment_status;
                    $result[] = $data;
                }

            } else {
            }
        }
        $responceResult['items'] = $result;
        $responceResult['logs'] = $logs;
        if ($dates->temp_start_date != null) {
            $responceResult['dates'] = ["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        } else {
            $responceResult['dates'] = ["start" => $temp_start_date, "end" => $temp_end_date];
        }
//        if ($report) {
//            return ["start" => $temp_start_date, "end" => $temp_end_date];
//        }
        return $responceResult;
    }

    public static function sortedContractLogs($items)
    {
        $sorted_contract_logs = array();
        foreach ($items as $contracts) {
            // code...
            $contract_details = array();
            $contract_details['hospital_name'] = $contracts['hospital_name'];
            $contract_details['agreement_name'] = $contracts['agreement_name'];
            $contract_details['contract_id'] = $contracts['contract_id'];
            $contract_details['contract_name'] = $contracts['contract_name'];
            $contract_details['practice_name'] = $contracts['practice_name'];
            $contract_details['physician_name'] = $contracts['physician_name'];

            $contracts_logs = array();
            $i = 0;
            foreach ($contracts['logs'] as $log) {
                $contracts_logs[$i]['current_user_status'] = $log->current_user_status;
                $contracts_logs[$i]['log_date'] = $log->log_date;
                $contracts_logs[$i]['duration'] = $log->duration;
                $contracts_logs[$i]['log_details'] = $log->log_details;
                $contracts_logs[$i]['log_id'] = $log->log_id;
                $contracts_logs[$i]['action'] = $log->action;
                $contracts_logs[$i]['approval_signature'] = $log->approval_signature;
                $contracts_logs[$i]['final_approval_date'] = $log->final_approval_date;
                $contracts_logs[$i]['manager_type_id'] = $log->manager_type_id;
                $contracts_logs[$i]['levels'] = $log->levels;
                $i++;
            }
            arsort($contracts_logs);

            $contract_details['logs'] = $contracts_logs;

            array_push($sorted_contract_logs, $contract_details);
        }
        return $sorted_contract_logs;
    }

    public static function datesForApprovalDashboard($user_id, $contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '')
    {
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $prior_month_end_date = '0000-00-00';

        foreach ($contracts_for_user as $contract_id) {
            $contract_obj = Contract::find($contract_id);
            $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                $prior_month_end_date = $res_pay_frequency['prior_date'];
            }
        }

        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        $dates = PhysicianLog::select(
            DB::raw("MIN(physician_logs.date) as temp_start_date"),
            DB::raw("MAX(physician_logs.date) as temp_end_date"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where(function ($query) use ($proxy_check_id) {
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                //->where("physician_logs.next_approver_user", "=", $user_id)
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            })
            ->where(function ($query1) use ($user_id) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->whereIN("physician_logs.contract_id", $contracts_for_user)
            ->where("physician_logs.date", "<=", $prior_month_end_date);
        if ($payment_type != 0) {
            $dates = $dates->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $dates = $dates->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $dates = $dates->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $dates = $dates->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $dates = $dates->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $dates = $dates->where('physician_contracts.physician_id', '=', $physician_id);
        }
        $dates = $dates->where('agreements.archived', '=', false)
            ->orderBy('physician_contracts.physician_id')
            ->orderBy('physician_logs.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.date')
            ->orderBy('physician_logs.next_approver_user', "DESC")
            ->first();
        return $dates;
    }


    public static function logIdsForApproval($user_id, $type, $payment_type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '')
    {
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        $agreement_ids = ApprovalManagerInfo::select("agreement_id")
            ->where("is_deleted", "=", '0')
            //->where("user_id", "=", $user_id)
            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->pluck("agreement_id");
        $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
            ->where("contracts.default_to_agreement", "=", "1")
            ->whereNull("contracts.deleted_at")
            ->whereIN("contracts.agreement_id", $agreement_ids);
        $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
            ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
            ->where("agreement_approval_managers_info.contract_id", ">", 0)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->whereNull("contracts.deleted_at")
            //->where("user_id", "=", $user_id)
            ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $logs = DB::table('physician_logs')
            ->select('physician_logs.id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id);
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id); //added this condition for checking with proxy approvers
            })
            ->whereIN("physician_logs.contract_id", $contracts_for_user);
        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('physician_logs.physician_id', '=', $physician_id);
        }
        $logs = $logs->where('agreements.archived', '=', false)
            ->where(function ($query1) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->pluck('physician_logs.id');
        return $logs;
    }

    public static function datesForPaymentStatusDashboard($user_id, $contracts, $contracts_string, $practiceArray, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '', $check_status)
    {
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $dates = PhysicianLog::select(
            DB::raw("MIN(physician_logs.date) as temp_start_date"),
            DB::raw("MAX(physician_logs.date) as temp_end_date"))
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->whereIN("physician_logs.contract_id", $contracts)
            ->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` INNER JOIN `amount_paid` ON
                        `physician_logs`.`contract_id`=`amount_paid`.`contract_id` AND `physician_logs`.`date` >= `amount_paid`.`start_date` AND
                         `physician_logs`.`date` <= `amount_paid`.`end_date` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                          `amount_paid`.`contract_id` IN(" . $contracts_string . "))");
        if ($start_date != '' && $end_date != '') {
            $dates = $dates->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $dates = $dates->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($contract_type != 0) {
            $dates = $dates->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $dates = $dates->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $dates = $dates->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $dates = $dates->where('physician_logs.practice_id', '=', $practice_id);
        } else if (is_practice_manager()) {
            $dates = $dates->whereIn('physician_logs.practice_id', $practiceArray);
        }
        if ($physician_id != 0) {
            $dates = $dates->where('contracts.physician_id', '=', $physician_id);
        }
        if ($check_status == 2) {
            $dates = $dates->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00");
        } elseif ($check_status != 0) {
            $dates = $dates->where("physician_logs.signature", "=", 0)
                ->where("physician_logs.approval_date", "=", "0000-00-00");
            if ($check_status == 3) {
                $dates = $dates->whereRaw("physician_logs.id IN (SELECT log_id FROM `log_approval_history` WHERE id IN (SELECT MAX(id) FROM `log_approval_history` GROUP BY log_id) AND approval_status=0)");
            }
        }
        /*add condition for remove approved logs appear more than 120 days*/
        $dates = $dates->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                          `physician_logs`.`signature` != 0 AND `physician_logs`.`approval_date` != '0000-00-00' AND DATE_ADD(LAST_DAY(date),INTERVAL 120 DAY) < date '" . date('Y-m-d') . "' )");
        $dates = $dates->where('agreements.archived', '=', false)
            ->first();
        return $dates;
    }

    public static function find_proxy_aaprovers($user_id)
    {
        //this function returns aaray of all proxy approver user ids including that user id
        $today = date('Y-m-d');
        $proxy_check_id = array();
        $proxy_check = DB::table("proxy_approver_details")
            ->select("proxy_approver_details.user_id")
            ->where("proxy_approver_details.proxy_approver_id", "=", $user_id)
            ->where("proxy_approver_details.start_date", "<=", $today)
            ->where("proxy_approver_details.end_date", ">=", $today)
            ->whereNull("proxy_approver_details.deleted_at")
            ->get();
        foreach ($proxy_check as $proxy_check) {
            $proxy_check_id[] = $proxy_check->user_id;
        }
        array_push($proxy_check_id, $user_id);
        return $proxy_check_id;
    }

    // Function to get physician contract month-wise data for approver dashboard report.
    public static function ApproverLogsReport($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $report = false)
    {
        $responceResult = array();
        $result = array();
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //$contracts = self::getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        $agreement_ids = ApprovalManagerInfo::select("agreement_id")
            ->where("is_deleted", "=", '0')
            //->where("userx_id", "=", $user_id)
            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->pluck("agreement_id");
        $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
            ->where("contracts.default_to_agreement", "=", "1")
            ->whereNull("contracts.deleted_at")
            ->whereIN("contracts.agreement_id", $agreement_ids);
        $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
            ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
            ->where("agreement_approval_managers_info.contract_id", ">", 0)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->whereNull("contracts.deleted_at")
            //->where("user_id", "=", $user_id)
            ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
            ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

        $ratesArray = ContractRate::contractRates($contracts_for_user);
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_end_date = '0000-00-00';;
        foreach ($contracts_for_user as $key => $contract_id) {
            $contract_obj = Contract::find($contract_id);
            if ($contract_obj) {
                $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
                if ($agreement_data) {
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                    if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                        $prior_month_end_date = $res_pay_frequency['prior_date'];
                    }
                } else {
                    $contracts_for_user->forget($key);
                    break;
                }
            } else {
                $contracts_for_user->forget($key);
                break;
            }
        }

        //$dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');

        $LogBreakup = PhysicianLog::select(
            DB::raw("physicians.id as physician_id, CONCAT(physicians.first_name , ' ' , physicians.last_name ) as physician_name"),
            DB::raw("COUNT(physician_logs.contract_id) as total_contracts_to_approve, SUM(physician_logs.duration) AS hours_to_approve"),
            DB::raw("contract_names.name"),
            DB::raw("contract_names.id as contract_name_id"),
            DB::raw("MIN(physician_logs.date) as log_min_date"),
            DB::raw("MAX(physician_logs.date) as log_max_date"),
            DB::raw("contracts.payment_type_id, contracts.wrvu_payments"),
            DB::raw("contracts.expected_hours as total_expected_hours"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.allow_max_hours as allow_max_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.annual_cap as annual_max_hours"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("DATE_FORMAT(physician_logs.date, '%M-%Y') as month"),
            DB::raw("contracts.rate as fmv_rate"),
            DB::raw("ROUND(SUM(physician_logs.duration * contracts.rate)) AS calculated_payment"),
            DB::raw("contract_types.id as contract_type_id"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id)
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            })
            ->where(function ($query1) use ($user_id) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->whereIN("physician_logs.contract_id", $contracts_for_user);
        if ($start_date != '' && $end_date != '') {
            $LogBreakup = $LogBreakup->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $LogBreakup = $LogBreakup->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        // check if payment_type scrollDown selected. If yes filter the logs
        if ($payment_type != 0) {
            $LogBreakup = $LogBreakup->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $LogBreakup = $LogBreakup->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $LogBreakup = $LogBreakup->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $LogBreakup = $LogBreakup->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $LogBreakup = $LogBreakup->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $LogBreakup = $LogBreakup->where('physician_logs.physician_id', '=', $physician_id);
        }
        $LogBreakup = $LogBreakup->where('agreements.archived', '=', false)
            ->groupBy('contracts.id')
            ->groupBy('month')
            ->groupBy('physician_logs.physician_id')
            ->orderBy('physicians.first_name', 'ASC')
            ->orderBy('physician_logs.date', 'ASC')
            ->distinct('month')
            ->get();


        // Below condition is added for checking the Approval selected and payment calculations.
        foreach ($LogBreakup as $key => $log_group_obj) {
            $log_group_obj->flagApprove = false;
            $log_group_obj->flagReject = true;

            $logs = self::LogsForApprover($user_id, $type, $log_group_obj->payment_type_id, $log_group_obj->contract_type_id, $log_group_obj->hospital_id, $log_group_obj->agreement_id, $log_group_obj->practice_id, $log_group_obj->physician_id, $log_group_obj->log_min_date, $log_group_obj->log_max_date, $agreement_ids, $contracts_for_user, $report = true, $log_group_obj->contract_id, $log_group_obj->contract_name_id);
            /**
             * Below code is used for gettting calculation from the respective payment type class for logs object.
             */

            $payment_type_factory = new PaymentTypeFactoryClass(); // This is a factory class used for returning the required payment type object of class based on parameter payment type.
            // log::info('$logsLevel2', array($logs));
            if (count($logs['sorted_logs_data']) > 0) {
                foreach ($logs['sorted_logs_data'] as $payment_type_id => $logs) {
                    $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($payment_type_id); // This line is returns the object for calculation based on payment type.
                    $result = $payment_type_obj->calculatePayment($logs, $ratesArray);
                }
                if ($log_group_obj->payment_type_id != PaymentType::PER_DIEM && $log_group_obj->payment_type_id != PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    if ($result['unique_date_range_arr'] != null) {
                        $calculated_expected_hours = number_format(count($result['unique_date_range_arr']) * $log_group_obj->total_expected_hours, 2);
                    } else {
                        $calculated_expected_hours = 0;
                    }
                } else {
                    $calculated_expected_hours = 0;
                }
                $log_group_obj['logs'] = $logs;
                $log_group_obj->calculated_payment = number_format($result['calculated_payment'], 2);
                $log_group_obj->hours_to_approve = number_format($result['hours'], 2);
                $log_group_obj->expected_hours = number_format(str_replace(",", "", $calculated_expected_hours), 2);   //added for remove comma
                $log_group_obj->expected_payment = number_format($result['expected_payment'], 2);
            } else {
                $LogBreakup->forget($key); // Remove the current object from the collection if logs not found.
            }
        }
//        log::debug('$LogBreakup', array($LogBreakup));
        $responceResult['AppprovalLogBreakup'] = $LogBreakup;
        return $responceResult;
    }

    public static function LogsForApproverPaymentStatus($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $agreement_ids, $contracts_for_user, $report = false, $contract_id = null, $contract_name_id = null)
    {
        $responceResult = array();
        $result = array();
        $sorted_result = array();
        $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //$contracts = self::getContracts($user_id, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id);
        if (count($contracts_for_user) == 0) {
            $agreement_ids = ApprovalManagerInfo::select("agreement_id")
                ->where("is_deleted", "=", '0')
                //->where("user_id", "=", $user_id)
                ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                ->pluck("agreement_id");
            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);
            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->whereNull("contracts.deleted_at")
                //->where("user_id", "=", $user_id)
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");
        }
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_end_date = '0000-00-00';
        foreach ($contracts_for_user as $contract_id) {
            $contract_obj = Contract::withTrashed()->findOrFail($contract_id);
            $agreement_data = Agreement::getAgreementData($contract_obj->agreement_id);
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            if ($prior_month_end_date <= $res_pay_frequency['prior_date']) {
                $prior_month_end_date = $res_pay_frequency['prior_date'];
            }
        }

        // log::info('$prior_month_end_date',array($prior_month_end_date));
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");

        // Below condition is added by akash for fetching the contract specific data for physician on level three.
        /*if(isset($contract_id)){
            $contracts_for_user = [$contract_id];
            $dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');
        } else {
            $dates = self::datesForApprovalDashboard($user_id,$contracts_for_user, $type, $payment_type, $contract_type, $hospital_id, $agreement_id , $practice_id , $physician_id, '', '');
        }*/

        $logs = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("if(contracts.payment_type_id=2,1,0) as payment_type_sort"),
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.details as log_details"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("actions.category_id as action_category"),
            DB::raw("physician_logs.signature as approval_signature"),
            DB::raw("physician_logs.approval_date as final_approval_date"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.next_approver_level as next_approver_level"),
            DB::raw("physician_logs.next_approver_user as next_approver_user"),
            DB::raw("physician_logs.entered_by as entered_by"),
            DB::raw("physician_logs.entered_by_user_type as entered_by_user_type"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.log_hours"),
            DB::raw("physicians.first_name as physician_first_name"),
            DB::raw("physicians.last_name as physician_last_name"),
            DB::raw("contracts.default_to_agreement as default_to_agreement"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("contracts.rate"),
            DB::raw("contracts.partial_hours"), // Partial shift hours for add_call_duratio_partial_hours_on_call.
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("hospitals.name as hospital_name"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
//            ->where("physician_logs.signature", "=", 0)
            ->whereNull("physician_logs.deleted_at");
//            ->where("physician_logs.approval_date", "=", "0000-00-00");

        // This condition is added to use this function for payment status dashboard precalculation where we need to fetch all logs not the user specific logs
        if ($user_id != 0) {
            $logs = $logs->where(function ($query) use ($proxy_check_id) {
                //$query->where("physician_logs.next_approver_user", "=", $user_id)
                $query->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
                ->Where("physician_logs.next_approver_user", "!=", 0);
            });
        }

        $logs = $logs->where(function ($query1) use ($user_id) {
            $query1->whereNull("physician_logs.deleted_at");
            // ->orWhere("physician_logs.deleted_at", "=", "");
        })/*added for soft delete*/
        ->whereIN("physician_logs.contract_id", $contracts_for_user);
        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        // check if payment_type scrollDown selected. If yes filter the logs
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('physician_logs.physician_id', '=', $physician_id);
        }
        if ($contract_name_id != null) {
            $logs = $logs->where('contracts.contract_name_id', '=', $contract_name_id);
        }
        $logs = $logs->where('agreements.archived', '=', false)
            ->orderBy('current_user_status_sort', "DESC")
            ->orderBy('payment_type_sort', "DESC")
            ->orderBy('physician_logs.physician_id')
            ->orderBy('physician_logs.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.date')
            ->orderBy('physician_logs.next_approver_user', "DESC");

        if ($report) {
            $logs = $logs->get();
        } else {
            $logs = $logs->paginate(10);
        }
        $prev_contract_id = 0;
        // call function for contracts where this user is assigned only as primary approver
        $primary_approver_contracts = Contract::get_primary_approver_contracts($user_id);
        foreach ($logs as $log) {
            $practice = Practice::where('id', '=', $log->practice_id)->first();
            if ($practice) {
                $level = array();
                $logsForApproval = array();
                $logdate = strtotime($log->log_date);
                //if ($log->next_approver_user == $user_id) {
                if (!(in_array($log->contract_id, $primary_approver_contracts))) {
                    $log->proxy = 'true';
                } else {
                    $log->proxy = 'false';
                }
                $log->current_user_status = "Waiting";
                if (in_array($log->next_approver_user, $proxy_check_id)) {
                    // $log->current_user_status = "Waiting";
                    if ($log->next_approver_user != $user_id) {
                        $log->proxy = 'true';
                    }
                    /*if($log->next_approver_user== $user_id)
                    {
                      $log->proxy_approver_true=false;
                    }
                    else
                    {
                      $log->proxy_approver_true=false;
                    }*/
                    $level[] = [
                        "status" => "Approved",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                } else {
                    // $log->current_user_status = "Pending";
                    $level[] = [
                        "status" => "Pending",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                }
                if ($prev_contract_id != $log->contract_id) {
                    $approval_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                        ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                        ->where("is_deleted", "=", '0')->orderBy("level")->get();
                    $prev_contract_id = $log->contract_id;
                }
                $log_approval = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", ">", 0)->orderBy("approval_managers_level")->get();
                if (count($log_approval) > 0) {
                    foreach ($log_approval as $approvals) {
                        $user = User::withTrashed()->findOrFail($approvals->user_id); //withTrashed is used to fetch the deleted approvals also to avoid 404 issue on approval dashboard by akash
                        $level[] = [
                            "status" => "Approved",
                            "name" => $user->first_name . ' ' . $user->last_name
                        ];
                    }
                    if (isset($approval_manager_data[$log->next_approver_level - 1])) {
                        $manager_type_id = $approval_manager_data[$log->next_approver_level - 1]->type_id;
                    } else {
                        $manager_type_id = 0;
                    }
                } else {
                    if ($log->next_approver_level > 0) {
                        $approval_current_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                            ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->where("level", "=", $log->next_approver_level)->first();

                        if ($approval_current_manager_data) {
                            $manager_type_id = $approval_current_manager_data->type_id;
                        } else {
                            $manager_type_id = 0;
                        }
                    } else {
                        $manager_type_id = 0;
                    }
                }
                if (count($level) < 7) {
                    for ($la = count($level); $la < 7; $la++) {
                        if (count($approval_manager_data) >= $la) {
                            $user = User::findOrFail($approval_manager_data[$la - 1]->user_id);
                            $user_name = $user->first_name . ' ' . $user->last_name;
                            $u_status = "Pending";
                            $rejection = DB::table('log_approval_history')->select('*')
                                ->where('log_id', '=', $log->log_id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            if ($rejection) {
                                if ($rejection->approval_status == 0 && $rejection->approval_managers_level == $la) {
                                    $user = User::findOrFail($rejection->user_id);
                                    $user_name = $user->first_name . ' ' . $user->last_name;
                                    $u_status = "Rejected";
                                    $isRejected = true;
                                }
                            }
                        } else {
                            $user_name = " ";
                            $u_status = "N/A";
                        }
                        $level[] = [
                            "status" => $u_status,
                            "name" => $user_name
                        ];
                    }
                }
                if (strtotime($temp_start_date) >= $logdate && strtotime($temp_end_date) > $logdate) {
                    $temp_start_date = $log->log_date;
                } elseif ((strtotime($temp_start_date) < $logdate && strtotime($temp_end_date) <= $logdate) || (strtotime($temp_end_date) == strtotime(date("Y-m-d")) && strtotime($temp_end_date) != $logdate)) {
                    $temp_end_date = $log->log_date;
                }
                if ($manager_type_id != -1) {
                    /// CHANGES HERE - if Contract_id and action_id match in
                    // ON CALL cahnge action name
                    //if ($log->contract_type_id == ContractType::ON_CALL){
                    if ($log->payment_type_id == PaymentType::PER_DIEM || $log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $oncallactivity = DB::table('on_call_activities')
                            ->select('name')
                            ->where("contract_id", "=", $log->contract_id)
                            ->where("action_id", "=", $log->action_id)
                            ->first();
                        if ($oncallactivity) {
                            $log->action = $oncallactivity->name;
                        }
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

                    // 6.1.15 custom headings as well as actions Start
                    if ($log->payment_type_id == PaymentType::TIME_STUDY) {
                        $custom_action = CustomCategoryActions::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            ->where('action_id', '=', $log->action_id)
                            ->where('action_name', '!=', null)
                            ->where('is_active', '=', true)
                            ->first();

                        $log->action = $custom_action ? $custom_action->action_name : $log->action;
                    }
                    // 6.1.15 custom headings as well as actions End
                    /////////////////////////////////////
                    $data['hospital_id'] = $log->hospital_id;
                    $data['hospital_name'] = $log->hospital_name;
                    $data['agreement_id'] = $log->agreement_id;
                    $data['agreement_name'] = $log->agreement_name;
                    $data['physician_name'] = $log->physician_first_name . ' ' . $log->physician_last_name;
                    $data['physician_id'] = $log->physician_id;
                    $data['contract_name_id'] = $log->contract_name_id;
                    $data['contract_name'] = $log->contract_name;
                    $data['contract_id'] = $log->contract_id;
                    $data['contract_type_id'] = $log->contract_type_id;
                    $data['practice_id'] = $log->practice_id;
                    $data['practice_name'] = $practice->name;
                    $data['current_user_status'] = $log->current_user_status;
                    $data['log_date'] = $log->log_date;
                    $data['duration'] = $log->duration;
                    $data['log_details'] = $log->log_details;
                    $data['log_id'] = $log->log_id;
                    $data['action'] = $log->action;
                    $data['action_id'] = $log->action_id;
                    $data['approval_signature'] = $log->approval_signature;
                    $data['final_approval_date'] = $log->final_approval_date;
                    $data['manager_type_id'] = $manager_type_id;
                    $data['levels'] = $level;
                    $data['submitted_by'] = $entered_by;
                    $data['proxy'] = $log->proxy;
                    $data['rate'] = $log->rate;
                    $data['payment_type_id'] = $log->payment_type_id;
                    $data['expected_hours'] = $log->expected_hours;
                    $data['min_hours'] = $log->min_hours;
                    $data['partial_hours'] = $log->partial_hours;
                    $data['log_hours'] = $log->log_hours;
                    $data['action_category'] = $log->action_category;
                    $result[] = $data;

                    // Below code is used for getting sorted log data based on payment_type.
                    $sorted_result[$log->payment_type_id][] = $data;
                }
            } else {
            }
        }
        $responceResult['items'] = $result;
        $responceResult['logs'] = $logs;
        $responceResult['sorted_logs_data'] = $sorted_result;
        //$responceResult['dates']=["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
//        if ($report) {
//            return ["start" => $temp_start_date, "end" => $temp_end_date];
//        }
        return $responceResult;
    }

    public static function PaymentStatusDashboardReport($user_id, $type, $payment_type, $contract_type, $hospital_id = 0, $agreement_id = 0, $practice_id = 0, $physician_id = 0, $start_date = '', $end_date = '', $check_status = 0, $report = false, $contract_id, $group_id, $approver)
    {
        $result = array();
        $practiceArray = array();
//        if (Auth::check()) { // This line is commented because now reports are generating using jobs where sessions are not available to check authentication.
            if ($group_id == Group::PRACTICE_MANAGER) {
                $practice = DB::table('practice_user')
                    ->where('user_id', '=', $user_id)
                    ->select('practice_id')->get();
                foreach ($practice as $practiceID) {
                    $practiceArray[] = $practiceID->practice_id;
                }
            }
//        }
        if (count($practiceArray) == 0) {
            $practiceArray[] = 0;
        }
        $contracts = DB::table('contracts')->select(
            DB::raw("contracts.id"))
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id');

        if ($type == -1 && $group_id == Group::PRACTICE_MANAGER && $group_id != Group::SUPER_HOSPITAL_USER && $group_id != Group::HOSPITAL_ADMIN) {
            $contracts = $contracts->join('practices', 'practices.hospital_id', '=', 'agreements.hospital_id');
            $contracts = $contracts->join('practice_user', 'practice_user.practice_id', '=', 'practices.id');
        } else {
            $contracts = $contracts->join('hospital_user', 'hospital_user.hospital_id', '=', 'agreements.hospital_id');
        }
        $contracts = $contracts->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id');

        if ($type == -1 && $group_id == Group::PRACTICE_MANAGER && $group_id != Group::SUPER_HOSPITAL_USER && $group_id != Group::HOSPITAL_ADMIN) {
            $contracts = $contracts->where('practice_user.user_id', '=', $user_id);
        } else {
            $contracts = $contracts->where('hospital_user.user_id', '=', $user_id);
        }
        if ($payment_type != 0) {
            $contracts = $contracts->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $contracts = $contracts->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $contracts = $contracts->where('agreements.hospital_id', '=', $hospital_id);
        }
        if (Auth::check()) {
            if ($group_id == Group::PRACTICE_MANAGER) {
                $contracts = $contracts->whereIn('physician_practice_history.practice_id', $practiceArray);
            }
        }

        if ($agreement_id != 0) {
            $contracts = $contracts->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $contracts = $contracts->where('physician_practice_history.practice_id', '=', $practice_id)
                ->where('physician_contracts.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $contracts = $contracts->where('physician_contracts.physician_id', '=', $physician_id);
        }
        if ($contract_id != 0) {
            $contracts = $contracts->where('contracts.id', '=', $contract_id);
        }

        if ($check_status == 4 && $approver > 0) {
            $proxy_check_id = self::find_proxy_aaprovers($approver);

            $agreement_ids = ApprovalManagerInfo::select("agreement_id")
                ->where("is_deleted", "=", '0')
                ->whereIn("user_id", $proxy_check_id)
                ->pluck("agreement_id");

            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);

            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->whereNull("contracts.deleted_at")
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id)
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id");

            $contracts = $contracts->whereIn('contracts.id', $contracts_for_user);
        }

        //$contracts = $contracts->orderBy('contracts.contract_name_id')->orderBy('physician_practice_history.practice_id')->get();
        $contracts = $contracts->where('agreements.archived', '=', false)->orderBy('physician_practice_history.physician_id')
            ->orderBy('physician_practice_history.practice_id')->orderBy('contract_names.name')->distinct()->pluck("contracts.id");
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $temp_start_date = date("Y-m-d");
        $temp_end_date = date("Y-m-d");
        if (count($contracts) > 0) {
            $contracts_string = implode(',', $contracts->toArray());
        } else {
            $contracts[] = 0;
            $contracts_string = 0;
        }
        $prior_month_end_date = "0000-00-00";
        foreach ($contracts as $contract) {
            $contract_obj = Contract::withTrashed()->findOrFail($contract);
            $agreement_details = Agreement::withTrashed()->findOrFail($contract_obj->agreement_id);
            $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
            $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
            $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement_details);

            if ($prior_month_end_date <= $res_pay_frequency['prior_date'] && $res_pay_frequency['prior_date'] < date('Y-m-d', strtotime(now()))) {
                $prior_month_end_date = $res_pay_frequency['prior_date'];
            }
        }

        $dates = self::datesForPaymentStatusDashboardReport($user_id, $contracts, $contracts_string, $practiceArray, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, '', '', $check_status, $group_id);
        $logs = PhysicianLog::select(
            DB::raw("if(physician_logs.next_approver_user=" . $user_id . ",1,0) as current_user_status_sort"),
            DB::raw("if(contracts.payment_type_id=2,1,0) as payment_type_sort"),
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.details as log_details"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("physician_logs.signature as approval_signature"),
            DB::raw("physician_logs.approval_date as final_approval_date"),
            DB::raw("physician_logs.entered_by as entered_by"),
            DB::raw("physician_logs.entered_by_user_type as entered_by_user_type"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.next_approver_level as next_approver_level"),
            DB::raw("physician_logs.next_approver_user as next_approver_user"),
            DB::raw("physicians.first_name as physician_first_name"),
            DB::raw("physicians.last_name as physician_last_name"),
            DB::raw("contracts.default_to_agreement as default_to_agreement"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id to apply filter
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.hospital_id as hospital_id"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("hospitals.name as hospital_name"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->whereIN("physician_logs.contract_id", $contracts);

        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        } else if ($group_id == Group::PRACTICE_MANAGER) {
            $logs = $logs->whereIn('physician_logs.practice_id', $practiceArray);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('physician_contracts.physician_id', '=', $physician_id);
        }
        if ($check_status == 2) {
            $logs = $logs->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00");
        } elseif ($check_status != 0) {
            $logs = $logs->where("physician_logs.signature", "=", 0)
                ->where("physician_logs.approval_date", "=", "0000-00-00");
            if ($check_status == 3) {
                $logs = $logs->whereRaw("physician_logs.id IN (SELECT log_id FROM `log_approval_history` WHERE id IN (SELECT MAX(id) FROM `log_approval_history` GROUP BY log_id) AND approval_status=0)");
            } else if ($check_status == 4 && $approver > 0) {
                $logs = $logs->where("physician_logs.next_approver_user", "=", $approver);
            } else if ($check_status == 1) {
                $logs = $logs->where("physician_logs.next_approver_user", "=", "0");
            }
        }

        /**
         * This block of code is added for checking the full amount paid for that contract fot that period logs if not then fetch those logs as pending payment.
         */
        // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
        $logs_for_calculation = PhysicianLog::select(
            DB::raw("physician_logs.date as log_date"),
            DB::raw("physician_logs.duration as duration"),
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
            DB::raw("actions.name as action"),
            DB::raw("physician_logs.practice_id as practice_id"),
            DB::raw("physician_logs.contract_id as contract_id"),
            DB::raw("physician_logs.physician_id as physician_id"),
            DB::raw("physician_logs.log_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
            DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.agreement_id as agreement_id"),
            DB::raw("IF(physician_logs.physician_id = " . $physician_id . ", 'Waiting', 'NA') as current_user_status")
        )
            ->distinct('physician_logs.id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->where("physician_logs.signature", "!=", 0)
            ->whereNull("physician_logs.deleted_at")
            ->where("physician_logs.approval_date", "!=", "0000-00-00")
            ->where("physician_logs.practice_id", '=', $practice_id)
            ->where("physician_logs.physician_id", '=', $physician_id)
            ->whereIn("physician_logs.contract_id", $contracts)
            ->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);

        if ($check_status == 4 && $approver > 0) {
            $logs_for_calculation = $logs_for_calculation->where("physician_logs.next_approver_user", "=", $approver);
        }
        $logs_for_calculation = $logs_for_calculation->get();

        $payment_type_factory = new PaymentTypeFactoryClass();

        $contract_obj = Contract::where('id', '=', $contracts[0])->first();
        $ratesArray = ContractRate::contractRates($contracts);

        if (count($logs_for_calculation) > 0) {
            $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($contract_obj->payment_type_id); // This line is returns the object for calculation based on payment type.
            $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation, $ratesArray);

            $calculated_payment = $calculation_result['calculated_payment'];

            /* $get_amount_paid = Amount_paid::where('start_date', '=', mysql_date($start_date))
                ->where('end_date', '=', mysql_date($end_date))
                ->where('physician_id', '=', $physician_id)
                ->whereIn('contract_id', $contracts)
                ->where('practice_id', '=', $practice_id)
                ->get(); */

            $get_amount_paid = Amount_paid::select('amount_paid.*')
                ->join('amount_paid_physicians', 'amount_paid_physicians.amt_paid_id', '=', 'amount_paid.id')
                ->where('amount_paid.start_date', '=', mysql_date($start_date))
                ->where('amount_paid.end_date', '=', mysql_date($end_date))
                ->where('amount_paid_physicians.physician_id', '=', $physician_id)
                ->whereIn('amount_paid.contract_id', $contracts)
                ->where('amount_paid.practice_id', '=', $practice_id)
                ->get();

            if (count($get_amount_paid) > 0) {
                $amount_paid = $get_amount_paid->sum('amountPaid');
                /**
                 * If the amount paid and calculated payment is same then do not fetch the record other-wise fetch the record and display as pending payment.
                 **/
                if ($amount_paid == $calculated_payment) {
                    $logs->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` INNER JOIN `amount_paid` ON
                                `physician_logs`.`contract_id`=`amount_paid`.`contract_id` AND `physician_logs`.`date` >= `amount_paid`.`start_date` AND
                                `physician_logs`.`date` <= `amount_paid`.`end_date` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                                `amount_paid`.`contract_id` IN(" . $contracts_string . ") and physician_logs.deleted_at is null and physician_logs.approved_by !=0 and physician_logs.approving_user_type != 0)");
                }
            }
        }

        // Full amount paid check block ends here.

        $logs = $logs->where('agreements.archived', '=', false)
            ->whereRaw("physician_logs.contract_id not in (select c.id from contracts c where c.payment_type_id = 4 and c.wrvu_payments = false)")
            ->orderBy('current_user_status_sort', "DESC")
            ->orderBy('payment_type_sort', "DESC")
            ->orderBy('physician_contracts.physician_id')
            //drop column practice_id from table 'physicians' changes by 1254
            ->orderBy('physician_contracts.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.date')
            ->orderBy('physician_logs.next_approver_user', "DESC");

        if ($report) {
            $logs = $logs->get();
        } else {
            $logs = $logs->paginate(10);
        }
        $prev_contract_id = 0;
        foreach ($logs as $log) {
            $practice = Practice::where('id', '=', $log->practice_id)->first();
            if ($practice) {
                $isRejected = false;
                $level = array();
                $logsForApproval = array();
                $logdate = strtotime($log->log_date);
                if ($log->next_approver_user == $user_id) {
                    $log->current_user_status = "Waiting";
                    $level[] = [
                        "status" => "Approved",
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                } else {
                    $log->current_user_status = "Pending";
                    $physician_approval_status = "Pending";
                    if ($log->approval_signature != 0 || $log->final_approval_date != '0000-00-00') {
                        $physician_approval_status = "Approved";
                    } else {
                        $log_approval_physician = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", "=", 0)->orderBy("approval_managers_level")->get();
                        if (count($log_approval_physician) > 0) {
                            $physician_approval_status = "Approved";
                        }
                    }
                    $level[] = [
                        "status" => $physician_approval_status,
                        "name" => $log->physician_first_name . ' ' . $log->physician_last_name
                    ];
                }
                if ($prev_contract_id != $log->contract_id) {
                    $approval_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                        ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                        ->where("is_deleted", "=", '0')->orderBy("level")->get();
                    $prev_contract_id = $log->contract_id;
                }
                $log_approval = LogApproval::where("log_id", "=", $log->log_id)->where("approval_status", "=", 1)->where("approval_managers_level", ">", 0)->orderBy("approval_managers_level")->get();
                if (count($log_approval) > 0) {
                    foreach ($log_approval as $approvals) {
                        $user = User::withTrashed()->findOrFail($approvals->user_id);
                        $level[] = [
                            "status" => "Approved",
                            "name" => $user->first_name . ' ' . $user->last_name
                        ];
                    }
                    if (isset($approval_manager_data[$log->next_approver_level - 1])) {
                        $manager_type_id = $approval_manager_data[$log->next_approver_level - 1]->type_id;
                    } else {
                        $manager_type_id = -1;
                    }
                } else {
                    if ($log->next_approver_level > 0) {
                       
                        $approval_current_manager_data = ApprovalManagerInfo::where("agreement_id", "=", $log->agreement_id)
                            ->where("contract_id", "=", $log->default_to_agreement == '1' ? 0 : $log->contract_id)
                            ->where("is_deleted", "=", '0')->where("user_id", "=", $log->next_approver_user)->where("level", "=", $log->next_approver_level)->first();
                        $manager_type_id = $approval_current_manager_data->type_id;
                    } else {
                        $manager_type_id = 0;
                    }
                }
                if (count($level) < 7) {
                    for ($la = count($level); $la < 7; $la++) {
                        if (count($approval_manager_data) >= $la) {
                            $user = User::withTrashed()->findOrFail($approval_manager_data[$la - 1]->user_id);
                            $user_name = $user->first_name . ' ' . $user->last_name;
                            $u_status = "Pending";
                            $rejection = DB::table('log_approval_history')->select('*')
                                ->where('log_id', '=', $log->log_id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            if ($rejection) {
                                if ($rejection->approval_status == 0 && $rejection->approval_managers_level == $la) {
                                    $u_status = "Rejected";
                                    $isRejected = true;
                                }
                            }
                        } else {
                            $user_name = " ";
                            $u_status = "N/A";
                        }
                        $level[] = [
                            "status" => $u_status,
                            "name" => $user_name
                        ];
                    }
                }
                if (strtotime($temp_start_date) >= $logdate && strtotime($temp_end_date) > $logdate) {
                    $temp_start_date = $log->log_date;
                } elseif ((strtotime($temp_start_date) < $logdate && strtotime($temp_end_date) <= $logdate) || (strtotime($temp_end_date) == strtotime(date("Y-m-d")) && strtotime($temp_end_date) != $logdate)) {
                    $temp_end_date = $log->log_date;
                }
                $addFlag = false;
                if ($check_status == 1 && !$isRejected) {
                    $addFlag = true;
                } elseif ($check_status == 3 && $isRejected) {
                    $addFlag = true;
                } elseif ($check_status == 0 || $check_status == 2 || $check_status == 4) {
                    $addFlag = true;
                }
                if ($addFlag) {
                    /// CHANGES HERE - if Contract_id and action_id match in
                    // ON CALL cahnge action name
                    //if ($log->contract_type_id == ContractType::ON_CALL){
                    if ($log->payment_type_id == PaymentType::PER_DIEM || $log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $oncallactivity = DB::table('on_call_activities')
                            ->select('name')
                            ->where("contract_id", "=", $log->contract_id)
                            ->where("action_id", "=", $log->action_id)
                            ->first();
                        if ($oncallactivity) {
                            $log->action = $oncallactivity->name;
                        }
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


                    /**
                     * Below line of code is added for checking the amount paid for the logs period.
                     */

                    $payment_status = 'NA';

                    if ($log->final_approval_date != '0000-00-00') {

                        $calculated_payment = 0.00;
                        $amount_paid = 0.00;

                        $agreement_details = Agreement::findOrFail($log->agreement_id);

                        // Get the frequency type for the agreement and then get the periods for that frequency type.
                        $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
                        $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
                        $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement_details);
                        $period_range = $res_pay_frequency['date_range_with_start_end_date'];

                        $period_min = '';
                        $period_max = '';
                        foreach ($period_range as $period) {
                            if (strtotime($log->log_date) >= strtotime($period['start_date']) && strtotime($log->log_date) <= strtotime($period['end_date'])) {
                                $period_min = $period['start_date'];
                                $period_max = $period['end_date'];
                            }
                        }

                        if ($period_min != '' && $period_max != '') {
                            $contracts_arr[] = $log->contract_id;
                            $agreement_ids[] = $log->agreement_id;

                            $ratesArray = ContractRate::contractRates($contracts_arr);

                            // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
                            $logs_for_calculation = PhysicianLog::select(
                                DB::raw("physician_logs.date as log_date"),
                                DB::raw("physician_logs.duration as duration"),
                                DB::raw("physician_logs.id as log_id"),
                                DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
                                DB::raw("actions.name as action"),
                                DB::raw("physician_logs.practice_id as practice_id"),
                                DB::raw("physician_logs.contract_id as contract_id"),
                                DB::raw("physician_logs.physician_id as physician_id"),
                                DB::raw("physician_logs.log_hours"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
                                DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.agreement_id as agreement_id"),
                                DB::raw("IF(physician_logs.physician_id = " . $log->physician_id . ", 'Waiting', 'NA') as current_user_status")
                            )
                                ->distinct('physician_logs.id')
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
                                ->where("physician_logs.signature", "!=", 0)
                                ->whereNull("physician_logs.deleted_at")
                                ->where("physician_logs.approval_date", "!=", "0000-00-00")
                                ->where("contracts.payment_type_id", '=', $log->payment_type_id)
                                ->where("contracts.agreement_id", '=', $log->agreement_id)
                                ->where("physician_logs.practice_id", '=', $log->practice_id)
                                ->where("physician_logs.physician_id", '=', $log->physician_id)
                                ->where("physician_logs.contract_id", '=', $log->contract_id)
                                ->whereBetween('physician_logs.date', [mysql_date($period_min), mysql_date($period_max)]);
                            if ($check_status == 4 && $approver > 0) {
                                $logs_for_calculation = $logs_for_calculation->where("physician_logs.next_approver_user", "=", $approver);
                            }
                            $logs_for_calculation = $logs_for_calculation->get();

                            $payment_type_factory = new PaymentTypeFactoryClass();
                            $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($log->payment_type_id); // This line is returns the object for calculation based on payment type.
                            $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation, $ratesArray);

                            $calculated_payment = $calculation_result['calculated_payment'];

                            $get_amount_paid = Amount_paid::where('start_date', '=', mysql_date($period_min))
                                ->where('end_date', '=', mysql_date($period_max))
//                                ->where('physician_id', '=', $log->physician_id)
                                ->where('contract_id', '=', $log->contract_id)
                                ->where('practice_id', '=', $log->practice_id)
                                ->get();

                            if (count($get_amount_paid) > 0) {
                                $amount_paid = $get_amount_paid->sum('amountPaid');
                            }
                            // log::info('$amount_paid', array($amount_paid));
                            // log::info('$calculated_payment', array($calculated_payment));
                            if ($amount_paid < $calculated_payment) {
                                $payment_status = 'Pending';
                            } else {
                                $payment_status = 'Paid'; // This will never show because if the logs are paid then it doesn't show up on the payment status dashboard.
                            }
                        }
                    }
                    // 6.1.15 custom headings as well as actions Start
                    if ($log->payment_type_id == PaymentType::TIME_STUDY) {
                        $custom_action = CustomCategoryActions::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            ->where('action_id', '=', $log->action_id)
                            ->where('action_name', '!=', null)
                            ->where('is_active', '=', true)
                            ->first();

                        $log->action = $custom_action ? $custom_action->action_name : $log->action;
                    }
                    // 6.1.15 custom headings as well as actions End
                    // Payment status check block ends here.

                    /////////////////////////////////////
                    $contract = Contract::FindOrFail($log->contract_id);
                    $fmv_rate_flag = false;
                    if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                        if ($contract->on_call_process && (($log->action_id == 2222 && $contract->on_call_rate > 0) || ($log->action_id == 2223 && $contract->called_back_rate > 0) || ($log->action_id == 2224 && $contract->called_in_rate > 0))) {
                            $fmv_rate_flag = true;
                        } else {
                            if ((($log->action_id == 1051 || $log->action_id == 1058) && $contract->weekday_rate > 0) || (($log->action_id == 1057 || $log->action_id == 1049) && $contract->weekend_rate > 0) || (($log->action_id == 1056 || $log->action_id == 1054) && $contract->holiday_rate > 0)) {
                                $fmv_rate_flag = true;
                            }
                        }
                    } else {
                        $fmv_rate_flag = true;
                    }

                    if ($fmv_rate_flag == true && $payment_status != 'Paid') {
                        $data['hospital_id'] = $log->hospital_id;
                        $data['hospital_name'] = $log->hospital_name;
                        $data['agreement_name'] = $log->agreement_name;
                        $data['physician_name'] = $log->physician_first_name . ' ' . $log->physician_last_name;
                        $data['contract_name_id'] = $log->contract_name_id;
                        $data['contract_name'] = $log->contract_name;
                        $data['contract_id'] = $log->contract_id;
                        $data['practice_id'] = $log->practice_id;
                        $data['practice_name'] = $practice->name;
                        $data['current_user_status'] = $log->current_user_status;
                        $data['log_date'] = $log->log_date;
                        $data['duration'] = $log->duration;
                        $data['log_details'] = $log->log_details;
                        $data['log_id'] = $log->log_id;
                        $data['action'] = $log->action;
                        $data['approval_signature'] = $log->approval_signature;
                        $data['final_approval_date'] = $log->final_approval_date;
                        $data['manager_type_id'] = $manager_type_id;
                        $data['levels'] = $level;
                        $data['submitted_by'] = $entered_by;
                        $data['payment_status'] = $payment_status;
                        $result[] = $data;
                    }
                }

            } else {
            }
        }
        $responceResult['items'] = $result;
        $responceResult['logs'] = $logs;
        if ($dates->temp_start_date != null) {
            $responceResult['dates'] = ["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        } else {
            $responceResult['dates'] = ["start" => $temp_start_date, "end" => $temp_end_date];
        }

        return $responceResult;
    }

    public static function datesForPaymentStatusDashboardReport($user_id, $contracts, $contracts_string, $practiceArray, $type, $contract_type, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '', $check_status, $group_id)
    {
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $dates = PhysicianLog::select(
            DB::raw("MIN(physician_logs.date) as temp_start_date"),
            DB::raw("MAX(physician_logs.date) as temp_end_date"))
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->whereIN("physician_logs.contract_id", $contracts)
            ->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` INNER JOIN `amount_paid` ON
                        `physician_logs`.`contract_id`=`amount_paid`.`contract_id` AND `physician_logs`.`date` >= `amount_paid`.`start_date` AND
                         `physician_logs`.`date` <= `amount_paid`.`end_date` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                          `amount_paid`.`contract_id` IN(" . $contracts_string . "))");
        if ($start_date != '' && $end_date != '') {
            $dates = $dates->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $dates = $dates->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($contract_type != 0) {
            $dates = $dates->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($hospital_id != 0) {
            $dates = $dates->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $dates = $dates->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $dates = $dates->where('physician_logs.practice_id', '=', $practice_id);
        } else if ($group_id == Group::PRACTICE_MANAGER) {    // is_practice_manager()
            $dates = $dates->whereIn('physician_logs.practice_id', $practiceArray);
        }
        if ($physician_id != 0) {
            $dates = $dates->where('contracts.physician_id', '=', $physician_id);
        }
        if ($check_status == 2) {
            $dates = $dates->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00");
        } elseif ($check_status != 0) {
            $dates = $dates->where("physician_logs.signature", "=", 0)
                ->where("physician_logs.approval_date", "=", "0000-00-00");
            if ($check_status == 3) {
                $dates = $dates->whereRaw("physician_logs.id IN (SELECT log_id FROM `log_approval_history` WHERE id IN (SELECT MAX(id) FROM `log_approval_history` GROUP BY log_id) AND approval_status=0)");
            }
        }
        /*add condition for remove approved logs appear more than 120 days*/
        $dates = $dates->whereRaw("physician_logs.id NOT IN (SELECT DISTINCT `physician_logs`.id FROM `physician_logs` WHERE `physician_logs`.`contract_id` IN(" . $contracts_string . ") AND
                          `physician_logs`.`signature` != 0 AND `physician_logs`.`approval_date` != '0000-00-00' AND DATE_ADD(LAST_DAY(date),INTERVAL 120 DAY) < date '" . date('Y-m-d') . "' )");
        $dates = $dates->where('agreements.archived', '=', false)
            ->first();
        return $dates;
    }
}