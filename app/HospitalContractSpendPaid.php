<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PDO;
use Log;
use App\Hospital;
use App\Jobs\UpdatePendingPaymentCount;

class HospitalContractSpendPaid extends Model
{
    protected $table = 'hospitals_contract_spend_paid';

    public static function updatePendingPaymentCount($hospital_id)
    {
        $check_exist = HospitalContractSpendPaid::select('*')
            ->where('hospital_id', '=', $hospital_id)
            ->get();

        if (count($check_exist) > 0) {
            $deployment_date = getDeploymentDate("ContractRateUpdate");
            $pending_payment_contract = array();
            try {
                $pdo = DB::connection()->getPdo();
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $stmt = $pdo->prepare('call sp_hospital_payment_require_info_v6(?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
                $stmt->bindValue((1), $hospital_id);
                $stmt->bindValue((2), $deployment_date->format('Y-m-d'));
                $exec = $stmt->execute();
                $spresults = [];


                do {
                    try {
                        $spresults[] = $stmt->fetchAll(PDO::FETCH_OBJ);
                    } catch (\Exception $ex) {
                        Log::info("SP call Catch Error 1 " . $ex->getMessage());
                    }
                } while ($stmt->nextRowset());
                // return $results;
            } catch (\Exception $e) {
                Log::info("SP call Catch Error 2 " . $e->getMessage());
            }
            if (count($spresults) > 0) {
                foreach ($spresults[1] as $result) {

                    // Log::info("SP call result", array($result));
                    if ($result->is_remaining_amount_flag == true) {
                        if (!array_key_exists($result->contract_id, $pending_payment_contract)) {
                            array_push($pending_payment_contract, $result->contract_id);
                        }
                    }
                }
            }
            $pending_payment_count = count($pending_payment_contract);
            $update_pending_payment_count = HospitalContractSpendPaid::where('hospital_id', '=', $hospital_id)->update(array('pending_payment_count' => $pending_payment_count));
        } else {
            $updateTotalSpendAndPaid = Hospital::postHospitalsContractTotalSpendAndPaid($hospital_id);
            if ($updateTotalSpendAndPaid > 0) //if active contracts count is greater than 0 then only we are checking for pending payment count here
            {
                UpdatePendingPaymentCount::dispatch($hospital_id);
            }
        }
        return true;
    }

    public static function getPendingPaymentCount($hospital_id)
    {
        $pending_payment_count = HospitalContractSpendPaid::select('pending_payment_count')
            ->where('hospital_id', '=', $hospital_id)
            ->first();
        return $pending_payment_count;
    }

    public static function updatePendingPaymentCountForAllHospitals()
    {
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            ->where('hospitals.archived', '=', 0)
            ->distinct()
            ->get();

        foreach ($hospitals as $hospital) {

            self::updatePendingPaymentCount($hospital->id);

//            $check_exist = HospitalContractSpendPaid::select('*')
//            ->where('hospital_id', '=', $hospital->id)
//            ->get();
//
//            if(count($check_exist)>0)
//            {

//                $deployment_date = getDeploymentDate("ContractRateUpdate");
//                $pending_payment_contract=array();
//                try
//                {
//                    $pdo = DB::connection()->getPdo();
//                    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
//                    $stmt = $pdo->prepare('call sp_hospital_payment_require_info_v6(?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
//                    $stmt->bindValue((1), $hospital->id);
//                    $stmt->bindValue((2), $deployment_date->format('Y-m-d'));
//                    $exec = $stmt->execute();
//                    $spresults = [];
//                    do {
//                        try {
//                            $spresults[] = $stmt->fetchAll(PDO::FETCH_OBJ);
//                        }
//                        catch (\Exception $ex) {
//                             Log::info("SP call Catch Error 3 " . $ex->getMessage());
//                        }
//                    } while ($stmt->nextRowset());
//                    // return $results;
//                }
//                catch (\Exception $e) {
//                     Log::info("SP call Catch Error 4 " . $e->getMessage());
//                }
//                if(count($spresults)>0){
//                    foreach ($spresults[1] as $result) {
//                        if($result->is_remaining_amount_flag == true)
//                        {
//                            if (!array_key_exists($result->contract_id, $pending_payment_contract)) {
//                                array_push($pending_payment_contract,$result->contract_id);
//                            }
//                        }
//                    }
//                }
//                $pending_payment_count=count($pending_payment_contract);
//                $update_pending_payment_count=HospitalContractSpendPaid::where('hospital_id','=',$hospital->id)->update(array('pending_payment_count'=>$pending_payment_count));
//            }
        }
        return true;
    }
}
