<?php
namespace App\customClasses;

use App\Agreement;
use App\Amount_paid;
use App\ContractRate;
use App\Contract;
use App\PhysicianLog;
use Illuminate\Support\Facades\Log;

class HourlyPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for hourly contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation.
     */
    public function calculatePayment($logs, $ratesArray) {
        try{
            if(count($logs) > 0){
                $contract = Contract::findOrFail($logs[0]['contract_id']);
                $this->temp_log_arr = array();
                $this->agreement_id_arr = array();
                $this->date_range_with_start_end_arr = array();
                foreach($logs as $key => $log){
                    if ($key == 0) {
                        $this->agreement_id = $log["agreement_id"];
                        $this->contract_id = $log["contract_id"];
                        $this->practice_id = $log["practice_id"];
                    }

                    if($log["current_user_status"] === 'Waiting'){
                        $this->flag_approve = true;
                        $logduration = $log['duration'];

                        foreach($ratesArray[ContractRate::FMV_RATE][$log['contract_id']] as $rates){
                            if(strtotime($rates['start_date'])<= strtotime($log['log_date']) && strtotime($rates['end_date']) >=strtotime($log['log_date']) ){
                                $this->rate = $rates['rate'];
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
                            $this->worked_hours_arr[$log_range_date] += $logduration;
                        } else {
                            array_push($this->unique_date_range_arr, $log_range_date);
                            $this->expected_payment += $log['expected_hours'] * $this->rate;
                            $this->worked_hours_arr[$log_range_date] = 0.00;
                            $this->worked_hours_arr[$log_range_date] += $logduration;
                            // Log::Info("month - $key", array($this->>unique_date_range_arr));
                        }

                        $this->hours += $logduration;
                        // $this->calculated_hourly_payment += $logduration * $this->rate;
                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                }

                if(count($this->worked_hours_arr)> 0){
                    foreach($this->worked_hours_arr as $month => $duration){
                        $date_period = $month;
						$calculated_payment_for_duration = 0;
                        $date_array = explode('-', $date_period);
                        $current_date_range = $this->date_range_with_start_end_arr[$contract->agreement_id][$date_array[2]];
                        $agreement_start_date = $contract->agreement->start_date;
                        $temp_agreement_end_date = $contract->agreement->end_date;
                        $contract_first_cytd = true; // used for prior amount paid calculation

                        while($agreement_start_date <= $temp_agreement_end_date){
                            $agreement_end_date = date('Y-m-d', strtotime("-1 day", strtotime("+1 year", strtotime($agreement_start_date))));
                            if ($current_date_range['start_date'] >= $agreement_start_date && $current_date_range['start_date'] <= $agreement_end_date){
                                break;
                            }
                            else{
                                $agreement_start_date = date('Y-m-d', strtotime("+1 year", strtotime($agreement_start_date)));
                                $contract_first_cytd = false;
                            }
                        }

                        if($contract_first_cytd && $contract->prior_amount_paid > 0){
                            $annual_max_payment = ($contract->annual_cap * $this->rate) - $contract->prior_amount_paid;
                        } else {
                            $annual_max_payment = $contract->annual_cap * $this->rate;
                        }

                        $amount_paid_cytd = Amount_paid::where('contract_id', '=', $contract->id)
                            ->where('practice_id', '=', $this->practice_id)
                            ->where('start_date', '>=', $agreement_start_date)
                            ->where('end_date', '<=', $agreement_end_date)
                            ->where('start_date', '!=', $current_date_range['start_date'])
                            ->where('end_date', '!=', $current_date_range['end_date'])
                            ->sum('amountPaid');

                        if($duration > $contract->max_hours) {
                            $calculated_payment_for_duration += $contract->max_hours * $this->rate;
                        } else {
                            $calculated_payment_for_duration += $duration * $this->rate;
                        }

                        $allowed_payment = $annual_max_payment - $amount_paid_cytd;

                        if($calculated_payment_for_duration > $allowed_payment) {
                            $calculated_payment_for_duration = $allowed_payment;
                        }

                        $this->calculated_hourly_payment += $calculated_payment_for_duration;
                    }
                }

                // log::info(' $this->calculated_hourly_payment', array( $this->calculated_hourly_payment));

                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    'calculated_payment' => ($this->calculated_hourly_payment < 0 ? 0 : $this->calculated_hourly_payment),
                    'flagApprove' => $this->flag_approve,
                    'flagReject' => $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from Hourly::calculatedPayment : logs array cannot be empty.');
            }
        }catch (\Exception $ex){
            Log::info("calculatePayment Hourly :" . $ex->getMessage());
        }
    }
}