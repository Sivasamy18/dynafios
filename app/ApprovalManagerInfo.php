<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalManagerInfo extends Model
{

    use SoftDeletes;

    protected $table = 'agreement_approval_managers_info';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function contract_managers($contract_id)
    {
        return self::contract_managers_info($contract_id);
    }

    public static function contract_managers_info($contract_id)
    {
        return self::where('is_deleted', '=', '0')
            ->where('contract_id', '=', $contract_id)
            ->get();
    }

    public static function approvalNotifymail($approved_logs, $role, $managers_data = array())
    {
        $contracts = Contract::select("contracts.*")
            ->join("physician_logs", "physician_logs.contract_id", "=", "contracts.id")
            ->whereIn("physician_logs.id", $approved_logs)
            ->distinct()->get();
        foreach ($contracts as $contract) {
            if ($contract->default_to_agreement == 0) {
                $checked_for_user_id = ProxyApprovalDetails::check_for_manager_id($contract->agreement_id, $contract->id, $role - 1);

                $current_manager_info = self::where("type_id", "=", $role - 1)
                    ->where("user_id", "=", $checked_for_user_id)
                    ->where("contract_id", "=", $contract->id)->first();

                if ($current_manager_info) {
                    $next_level_info = self::where("level", "=", ($current_manager_info->level) + 1)
                        ->where("contract_id", "=", $contract->id)
                        ->where("is_deleted", "=", "0")->get();
                } else {
                    $next_level_info = array();
                }
            } else {

                $checked_for_user_id = ProxyApprovalDetails::check_for_manager_id($contract->agreement_id, 0, $role - 1);
                $current_manager_info = self::where("type_id", "=", $role - 1)
                    ->where("user_id", "=", $checked_for_user_id)
                    ->where("agreement_id", "=", $contract->agreement_id)
                    ->where("contract_id", "=", 0)
                    ->first();

                if ($current_manager_info) {
                    $next_level_info = self::where("level", "=", ($current_manager_info->level) + 1)
                        ->where("agreement_id", "=", $contract->agreement_id)
                        ->where("contract_id", "=", 0)
                        ->where("is_deleted", "=", "0")->get();
                } else {
                    $next_level_info = array();
                }
            }


            if (count($next_level_info) > 0) {
                if ($next_level_info[0]->opt_in_email_status == 1) {
                    if (!array_key_exists($next_level_info[0]->user_id, $managers_data)) {
                        $managers_data[$next_level_info[0]->user_id]["contracts"] = array();
                    }
                    $managers_data[$next_level_info[0]->user_id]["contracts"][$contract->contract_name_id] = $contract->contractName->name;
                }
            }
        }
        return $managers_data;
    }

    public static function getLogManagerType($log_id)
    {
        $log = PhysicianLog::findOrFail($log_id);
        $contract = Contract::findOrFail($log->contract_id);
        $manager_type_id = self::select("type_id")->where('is_deleted', '=', '0')
            ->where('contract_id', '=', $contract->default_to_agreement == 0 ? $contract->id : 0)
            ->where('level', '=', $log->next_approver_level)
            ->where('user_id', '=', $log->next_approver_user)
            ->where('agreement_id', '=', $contract->agreement_id)->first();
        if ($manager_type_id) {
            return $manager_type_id->type_id;
        } else {
            return 0;
        }
    }
}
