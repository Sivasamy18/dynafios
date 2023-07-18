<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Action extends Model
{
    protected $table = 'actions';

    public static function activities($paymentTypeId)
    {

        if ($paymentTypeId == PaymentType::PER_DIEM || $paymentTypeId == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            return self::where('action_type_id', '=', 1)
                //->where('contract_type_id', '=', $contractTypeId)
                ->where('payment_type_id', '=', $paymentTypeId)
                ->orderBy('sort_order')
                ->get();
        } else {
            return self:://where('action_type_id', '=', 1)
            //->where('contract_type_id', '=', $contractTypeId)
            where('category_id', '>', 0)
                ->orderBy('name')
                ->get();
        }


    }

    public static function duties($paymentTypeId)
    {
        return self::where('action_type_id', '=', 2)
            //->where('contract_type_id', '=', $contractTypeId)
            ->where('payment_type_id', '=', $paymentTypeId)
            ->get();
    }

    public static function getActions($contract)
    {


//        $practiceId = DB::table("contracts")
//                            ->select("contracts.practice_id")
//                            ->where("contracts.id","=",$contract->id)->first();
        $hospital_id = Contract::
//            ->select("agreements.hospital_id")
        join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->where("contracts.id", "=", $contract->id)
            ->pluck("agreements.hospital_id");

//        $hospitalId = DB::table("practices")->select("practices.hospital_id")->where("practices.id","=",$practiceId->practice_id)->first();

        // If we have actions on_call, called_back, called-in sort by action_id else sort by action_name
        //if($contract->on_call_rate > 0 || $contract->called_back_rate > 0 || $contract->called_in_rate > 0) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $orderBy = "sort_order";
        } else {
            $orderBy = "name";
        }

        //Action-Redesign by 1254

        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $activities = $contract->actions()
                ->whereIn("action_type_id", [1, 2])
                ->orderBy("action_type_id", "asc")
                ->orderBy($orderBy, "asc")
                ->get();

        } else if ($contract->payment_type_id == PaymentType::PER_UNIT) {
            $activities = $contract->actions()
                ->whereIn("hospital_id", [0, $hospital_id])
                ->where("payment_type_id", "=", 8)
                ->distinct()
                ->get();
        } else {

            $activities = self::select('actions.*')
                ->join("sorting_contract_activities", "sorting_contract_activities.action_id", "=", "actions.id")    // 6.1.13
                ->whereIn("actions.hospital_id", [0, $hospital_id])
                ->where("actions.payment_type_id", "!=", 5)   //issue fixed :getting uncompensated type action to stipend and hourly type
                ->where("sorting_contract_activities.contract_id", "=", $contract->id)
                ->where('sorting_contract_activities.is_active', '=', 1)
                ->orderBy("sorting_contract_activities.sort_order", "asc")  // 6.1.13
                ->distinct()
                ->get();
        }

        $results = [];

        foreach ($activities as $activity) {

            //if($contract->contract_type_id==ContractType::ON_CALL)  /*remove to add payment type */
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                $changed_name = OnCallActivity::where("action_id", "=", $activity->id)->where("contract_id", "=", $contract->id)->first();
                if (($activity->name == 'Holiday - HALF Day - On Call') || ($activity->name == 'Holiday - FULL Day - On Call')) {
                    // if($contract->holiday_rate >0) /**** Old condition changed to accept 0 rates */
                    if ($contract->holiday_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                } elseif (($activity->name == 'Weekend - HALF Day - On Call') || ($activity->name == 'Weekend - FULL Day - On Call')) {
                    // if($contract->weekend_rate > 0)   /**** Old condition changed to accept 0 rates */
                    if ($contract->weekend_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                } elseif (($activity->name == 'Weekday - HALF Day - On Call') || ($activity->name == 'Weekday - FULL Day - On Call')) {
                    // if($contract->weekday_rate > 0)   /**** Old condition changed to accept 0 rates */
                    if ($contract->weekday_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                } elseif (($activity->name == 'On-Call')) {
                    // if($contract->on_call_rate >0)   /**** Old condition changed to accept 0 rates */
                    if ($contract->on_call_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                } elseif (($activity->name == 'Called-Back')) {
                    // if($contract->called_back_rate > 0)  /**** Old condition changed to accept 0 rates */
                    if ($contract->called_back_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                } else {
                    // if($contract->called_in_rate > 0)    /**** Old condition changed to accept 0 rates */
                    if ($contract->called_in_rate >= 0) {
                        $results[] = [
                            "id" => $activity->id,
                            "name" => $activity->name,
                            "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                            "action_type_id" => $activity->action_type_id,
                            "action_type" => $activity->actionType->name,
                            "duration" => DB::table("action_contract")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $activity->id)
                                ->value("hours"),
                            "time_stamp_entry" => false,
                            "override_mandate" => false
                        ];
                    }
                }
            } else if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $changed_name = OnCallActivity::where("action_id", "=", $activity->id)->where("contract_id", "=", $contract->id)->first();
                $results[] = [
                    "id" => $activity->id,
                    "name" => $activity->name,
                    "display_name" => ($changed_name) ? $changed_name->name : $activity->name,
                    "action_type_id" => $activity->action_type_id,
                    "action_type" => $activity->actionType->name,
                    "duration" => DB::table("action_contract")
                        ->where("contract_id", "=", $contract->id)
                        ->where("action_id", "=", $activity->id)
                        ->value("hours"),
                    "time_stamp_entry" => false,
                    "override_mandate" => false
                ];
            } else if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_actions = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $activity->id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $results[] = [
                    "id" => $activity->id,
                    "name" => $activity->name,
                    "display_name" => $custom_actions ? $custom_actions->action_name : $activity->name,
                    "action_type_id" => $activity->action_type_id,
                    "action_type" => $activity->action_type_id > 0 ? $activity->actionType->name : '-',
                    "duration" => DB::table("action_contract")
                        ->where("contract_id", "=", $contract->id)
                        ->where("action_id", "=", $activity->id)
                        ->value("hours"),
                    "category_id" => $activity->category_id
                ];
            } else {

                $time_stamp_entry = HospitalTimeStampEntry::select('*')
                    ->whereIn("hospital_id", [0, $hospital_id])
                    ->where('action_id', '=', $activity->id)
                    ->where('is_active', '=', 1)
                    ->count();

                $override_mandate = HospitalOverrideMandateDetails::select('*')
                    ->whereIn("hospital_id", [0, $hospital_id])
                    ->where('action_id', '=', $activity->id)
                    ->where('is_active', '=', 1)
                    ->count();

                $results[] = [
                    "id" => $activity->id,
                    "name" => $activity->name,
                    "display_name" => $activity->name,
                    "action_type_id" => $activity->action_type_id,
                    "action_type" => $activity->action_type_id > 0 ? $activity->actionType->name : '-',
                    "duration" => DB::table("action_contract")
                        ->where("contract_id", "=", $contract->id)
                        ->where("action_id", "=", $activity->id)
                        ->value("hours"),
                    "time_stamp_entry" => $time_stamp_entry > 0 ? true : false,
                    "override_mandate" => $override_mandate > 0 ? true : false
                ];


            }

        }

        return $results;
    }

    /**
     *
     */
    public function actionType()
    {
        return $this->belongsTo("App\ActionType");
    }

    public function contractType()
    {
        return $this->belongsTo("App\ContractType");
    }

    public function contracts()
    {
        return $this->belongsToMany('App\Contract');
    }

    //public static function activities($contractTypeId) {

    public function physicianLogs()
    {
        return $this->hasMany('App\PhysicianLog');
    }

    //public static function duties($contractTypeId) {

    public function physicians()
    {
        return $this->belongsToMany('App\Physician');
    }

    public function paymentType()
    {
        return $this->belongsTo("App\PaymentType");
    }
}
