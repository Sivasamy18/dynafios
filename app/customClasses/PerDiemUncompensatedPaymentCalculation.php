<?php
namespace App\customClasses;

use App\Agreement;
use App\ContractRate;
use App\PaymentType;
use Illuminate\Support\Facades\Log;

class PerDiemUncompensatedPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for per-diem uncompensated contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation. Further we will generate ratesArray in this function from the data we recieve.
     */
    public function calculatePayment($logs, $ratesArray) {
        try{
            if(count($logs) > 0){

                $i = 0;
                $ratesArray = array();
                $payment_ranges = array();
                $this->agreement_id_arr = array();
                $this->date_range_with_start_end_arr = array();
                foreach($logs as $log){

                    /**
                     * Below condition will run only once to calculate ratesArray for different ranges available for the contract.
                     */
                    if($i == 0){
                        $contractRates = ContractRate::where('contract_id','=',$log['contract_id'])
                            ->where("status","=",'1')
                            ->orderBy("effective_start_date",'DESC')
                            ->get();
                        $temp_range_arr = array();
                        $effective_start_date = "";
                        $effective_end_date = "";
                        foreach($contractRates as $contractRate){
                            $temp_range = ["rate_index" => $contractRate->rate_index,
                                "start_day" => $contractRate->range_start_day,
                                "end_day" => $contractRate->range_end_day,
                                "rate" => $contractRate->rate];
                            array_push($temp_range_arr, $temp_range);
                            $effective_start_date = $contractRate->effective_start_date;
                            $effective_end_date = $contractRate->effective_end_date;
                        }

                        $ratesArray[]=["start_date" => $effective_start_date,
                            "end_date" => $effective_end_date,
                            "range" => $temp_range_arr
                        ];
                    }

                    if($log["current_user_status"] === 'Waiting'){
                        $this->flag_approve = true;
                        $logduration = $log['duration'];

                        foreach($ratesArray as $rates){
                            if(strtotime($rates['start_date'])<= strtotime($log['log_date']) && strtotime($rates['end_date']) >=strtotime($log['log_date']) ){
                                $payment_ranges = $rates['range'];
                            }
                        }

                        $logDate=strtotime($log['log_date']);
                        $log_month=date("F",$logDate);
                        $log_year=date("Y",$logDate);
                        $log_date= $log['contract_id'] . '-' . $log_month . '-' . $log_year;

                        if(array_key_exists( $log["agreement_id"] ,$this->date_range_with_start_end_arr )) {
                            // do something.
                        } else {
                            $agreement_data = Agreement::getAgreementData($log["agreement_id"]);
                            $payment_type_factory = new PaymentFrequencyFactoryClass();
                            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                            $date_range_arr = $res_pay_frequency['date_range_arr'];
                            $this->date_range_with_start_end_arr[$log["agreement_id"]] = $res_pay_frequency['date_range_with_start_end_date'];
                        }

                        if(array_key_exists( $log["agreement_id"] ,$this->date_range_with_start_end_arr) ){
                            foreach($this->date_range_with_start_end_arr as $agreement_id => $dates_arr){
                                foreach($dates_arr as $range_index => $date_arr){
                                    $temp_start_date = strtotime($date_arr['start_date']);
                                    $temp_end_date = strtotime($date_arr['end_date']);
                                    $check_log_date = strtotime($log["log_date"]);
                                    if($check_log_date >= $temp_start_date && $check_log_date <= $temp_end_date){
                                        $log_range_date = $log['contract_id'] . '-' . $log['agreement_id'] . '-' . $range_index;
                                    }
                                    // log::info('$range_index', array($date_arr['start_date']));
                                }
                            }
                        }

                        /**
                         * Below condition is used for calculating worked_hours and expected_payment based on unique month
                         */
                        if(in_array( $log_range_date ,$this->unique_date_range_arr )) {
                            $this->worked_hours_arr[$log_range_date] += $log['log_hours'];
                        } else {
                            array_push($this->unique_date_range_arr, $log_range_date);
                            $this->expected_payment += $log['expected_hours'] * $this->rate;
                            $this->worked_hours_arr[$log_range_date] = 0.00;
                            $this->worked_hours_arr[$log_range_date] += $log['log_hours'];
                            // Log::Info("month - $key", array($this->>unique_date_range_arr));
                        }

                        if($log['payment_type_id'] == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS && $log['partial_hours'] == 1){
                            $this->hours += $logduration;
                        }

                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                    $i++;
                }

                /**
                 * Below is condition used for calculating payment based on payment ranges for the contract.
                 */
                foreach($this->worked_hours_arr as $val_day_hour){
                    $total_day = $val_day_hour;
                    $total_day = round($total_day, 10);
                    $temp_day_remaining = $total_day;
                    $temp_calculated_payment = 0.00;

                    foreach($payment_ranges as $range_val_arr){
                        $start_day = 0;
                        $end_day = 0;
                        $rate = 0.00;
                        extract($range_val_arr); // This line will convert the key into variable to create dynamic ranges from received data.
                        if($total_day >= $start_day){
                            if($temp_day_remaining > 0){
                                $days_in_range = ($end_day - $start_day) + 1; // Calculating the number of days in a range.
                                if($temp_day_remaining < $days_in_range){
                                    $temp_calculated_payment += $temp_day_remaining * $rate;
                                }else {
                                    $temp_calculated_payment += $days_in_range * $rate;
                                }
                                $temp_day_remaining = $temp_day_remaining - $days_in_range;
                            }
                        } else if($temp_day_remaining >= 0){
                            $temp_calculated_payment += $temp_day_remaining * $rate;
                            $temp_day_remaining = 0;
                        }
                        // Log::Info('rem', array($temp_day_remaining));
                        // Log::Info('test', array($temp_calculated_payment));
                    }
                    $this->calculated_payment = $this->calculated_payment + $temp_calculated_payment;
                }

                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    // 'calculated_payment' => number_format($this->calculated_payment, 2),
                    'calculated_payment' => $this->calculated_payment,
                    'flagApprove' => $this->flag_approve,
                    'flagReject' =>  $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from PerDiemUncompensatedPaymentCalculation::calculatedPayment : logs array cannot be empty.');
            }
        }catch (\Exception $ex){
            Log::info("calculatePayment Per-Diem-Uncompensated :" . $ex->getMessage());
        }
    }
}