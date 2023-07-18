<?php
namespace App\customClasses;

use App\Action;
use App\Agreement;
use App\ContractRate;
use App\PaymentType;
use Illuminate\Support\Facades\Log;

class PerDiemPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for per-diem contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation.
     */
    public function calculatePayment($logs, $ratesArray) {
        try{
            if(count($logs) > 0){
                $this->temp_log_arr = array();
                $this->agreement_id_arr = array();
                $this->date_range_with_start_end_arr = array();

                /**
                 * $perdiem_log_action_id is used for getting the action names which will required for setting the $ratetype variable.
                 */
                $perdiem_log_action_id=Action::where('payment_type_id','=',PaymentType::PER_DIEM)
                    ->where('action_type_id','!=',5)//for not including custom action
                    ->pluck('name','id')->toArray();

                foreach($logs as $log){
                    if($log["current_user_status"] === 'Waiting'){
                        $this->flag_approve = true;
                        $logduration = $log['duration'];

                        if (strlen(strstr(strtoupper($perdiem_log_action_id[$log['action_id']]), "WEEKDAY")) > 0) {
                            $ratetype=ContractRate::WEEKDAY_RATE;
                        } else if (strlen(strstr(strtoupper($perdiem_log_action_id[$log['action_id']]), "WEEKEND")) > 0) {
                            $ratetype=ContractRate::WEEKEND_RATE;
                        } else if (strlen(strstr(strtoupper($perdiem_log_action_id[$log['action_id']]), "HOLIDAY")) > 0) {
                            $ratetype=ContractRate::HOLIDAY_RATE;
                        }else if ($perdiem_log_action_id[$log['action_id']] == "On-Call") {
                            $ratetype=ContractRate::ON_CALL_RATE;
                        } else if ($perdiem_log_action_id[$log['action_id']] == "Called-Back") {
                            $ratetype=ContractRate::CALLED_BACK_RATE;
                        } else if ($perdiem_log_action_id[$log['action_id']] == "Called-In") {
                            $ratetype=ContractRate::CALLED_IN_RATE;
                        }

                        foreach($ratesArray[$ratetype][$log['contract_id']] as $rates){
                            if(strtotime($rates['start_date'])<= strtotime($log['log_date']) && strtotime($rates['end_date']) >=strtotime($log['log_date']) ){
                                $this->rate = $rates['rate'];
                            }
                        }

                        /**
                         * Below is condition used for calculating payment based on partial_hours ON/OFF
                         */
                        if($log['payment_type_id'] == PaymentType::PER_DIEM && $log['partial_hours'] == 1){
                            $logpayment = $log['log_hours'] * $this->rate;
                            $this->hours += $logduration;
                        } else {
                            $logpayment = $logduration * $this->rate;
                        }


                        $this->calculated_payment = $this->calculated_payment + $logpayment;
                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                }
                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    'calculated_payment' => $this->calculated_payment,
                    'flagApprove' => $this->flag_approve,
                    'flagReject' =>  $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from Stipend::calculatedPayment : logs array cannot be empty.');
            }
        }catch (\Exception $ex){
            Log::info("calculatePayment Per-Diem :" . $ex->getMessage());
        }
    }
}