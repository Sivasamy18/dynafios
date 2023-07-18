<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Log;

class ContractName extends Model
{
    protected $table = 'contract_names';

    public function contracts()
    {
        return $this->hasMany('App\Contract');
    }

    public function contractTemplates()
    {
        return $this->hasMany('App\ContractTemplate');
    }

    public function contractType()
    {
        return $this->belongsTo('App\ContractType');
    }

    public function paymentType()
    {
        return $this->belongsTo('App\PaymentType');
    }

    public static function options($payment_type_id)
    {
        return self::where("payment_type_id", "=", $payment_type_id)
            ->orderBy("name")
            ->pluck("name", "id");
    }

    public static function getName($id)
    {
        $contract_name = self::where("id", "=", $id)
            ->first();

        return $contract_name->name;
    }

    public static function getHospitalContractNameOptions($hospital_id)
    {
        $defaults = ["No Contract Name Filter" => ""];

        $contract_names = DB::table("contract_names")
            ->select("contract_names.id as id", "contract_names.name as name")
            ->join("contracts", "contracts.contract_name_id", "=", "contract_names.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->where("agreements.hospital_id", "=", $hospital_id)
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy("contract_names.id")
            ->orderBy("contract_names.name")
            ->pluck("name", "id");

        return $defaults + $contract_names;
    }

    public static function getPracticeContractNameOptions($practice_id)
    {
        $defaults = ["No Contract Name Filter" => ""];

        //drop column practice_id from table 'physicians' changes by 1254
        $contract_names = DB::table("contract_names")
            ->select("contract_names.id as id", "contract_names.name as name")
            ->join("contracts", "contracts.contract_name_id", "=", "contract_names.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("physicians", "physicians.id", "=", "contracts.physician_id")
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join("practices", "practices.id", "=", "physician_practices.practice_id")
            ->where("practices.id", "=", $practice_id)
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy("contract_names.id")
            ->orderBy("contract_names.name")
            ->pluck("name", "id");

        return $defaults + $contract_names;
    }

    public static function getPhysicianContractNameOptions($physician_id)
    {
        $defaults = ["No Contract Name Filter" => ""];

        $contract_names = DB::table("contract_names")
            ->select("contract_names.id as id", "contract_names.name as name")
            ->join("contracts", "contracts.contract_name_id", "=", "contract_names.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("physicians", "physicians.id", "=", "contracts.physician_id")
            ->where("physicians.id", "=", $physician_id)
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy("contract_names.id")
            ->orderBy("contract_names.name")
            ->pluck("name", "id");

        return $defaults + $contract_names;
    }

    //Chaitraly:: for contract name functionality for performance dashboard
    public static function getContractNamesForPerformanceDashboard($user_id, $selected_hospital, $hospital, $selected_agreement, $agreements, $practices, $selected_practice, $physician_id, $physicians, $payment_types, $payment_type, $contract_type, $contract_types)
    {
        // if(count($physician_id)==0){
        //     $physician_ids=array_keys($physicians);
        // }else{
        //     $physician_ids = $physician_id;
        // }
        $physician_ids = array_keys($physicians);
        // if(count($contract_type) > 0)
        // {
        //         $contract_type_id =$contract_type;
        // }
        // else
        // {
        //     $contract_type_id=array_keys($contract_types);
        // }
        $contract_type_id = array_keys($contract_types);
        // if(count($selected_practice) == 0) {
        //     $practice_ids=array_keys($practices);
        // }else{
        //             $practice_ids =$selected_practice;
        // }
        $practice_ids = array_keys($practices);
        // if(count($selected_agreement) == 0) {
        //     $agreement_ids=array_keys($agreements);
        // }else{
        //      $agreement_ids =$selected_agreement;
        // }

        $agreement_ids = array_keys($agreements);

        $contract_names = Hospital::fetch_contract_names_for_hospital_users($user_id, $selected_hospital, $payment_type, $contract_types);
        // Log::info("contractname.php contract name return",array($contract_names));


        $contract_name_list = array();

        foreach ($contract_names as $contract_name) {
            // $contract_name_existance = Contract::where("contract_name_id","=",$contract_name['contract_name_id'])
            //     ->whereIn("physician_id",$physician_ids)
            //     ->whereIn("contract_type_id",$contract_type_id)
            //     ->get();
            $contract_name_existance = Contract::
            // where("contract_type_id","=",$contract_types['contract_type_id'])
            join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                ->whereIn("agreements.id", $agreement_ids)
                ->where('agreements.archived', '=', false)
                ->whereIn("physician_practices.practice_id", $practice_ids)
                ->whereIn("contracts.physician_id", $physician_ids)
                ->where("payment_type_id", "=", $payment_type)
                ->whereNotIn("payment_type_id", [3, 5])
                ->whereIn("contract_type_id", $contract_type_id)
                ->where("contract_name_id", "=", $contract_name['contract_name_id'])
                ->get();

            if (count($contract_name_existance) > 0) {
                $contract_name_id = $contract_name['contract_name_id'];
                $contract_name = $contract_name['contract_name'];
                if (!in_array($contract_name_id, $contract_name_list)) {
                    $contract_name_list[$contract_name_id] = $contract_name;
                }
            }
        }
        return $contract_name_list;
    }

}
