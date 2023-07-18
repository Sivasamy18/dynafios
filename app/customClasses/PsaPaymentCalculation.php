<?php
namespace App\customClasses;

use App\Contract;
use Illuminate\Support\Facades\Log;

class PsaPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for PSA contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation.
     */
    public function calculatePayment($logs, $ratesArray) {
        try{
            if(count($logs) > 0){
                foreach($logs as $log){
                    if($log["current_user_status"] === 'Waiting'){
                        $this->flag_approve = true;
                        $logduration = $log['duration'];

                        if($logs['wrvu_payments']){
                            $this->rate = Contract::getPsaRate($log['contract_id'],$logduration);
                        }

                        $this->hours += $logduration;
                        $this->calculated_psa_payment += $logduration * $this->rate;
                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                }
                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    'calculated_payment' => $this->calculated_psa_payment,
                    'flagApprove' => $this->flag_approve,
                    'flagReject' => $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from Stipend::calculatedPayment : logs array cannot be empty.');
            }
        }catch (\Exception $ex){
            Log::info("calculatePayment PSA :" . $ex->getMessage());
        }
    }
}