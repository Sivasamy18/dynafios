<?php

namespace App\customClasses;

use App\Agreement;
use App\ContractRate;
use App\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RehabPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for hourly contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation.
     */
    public function calculatePayment($logs, $ratesArray)
    {
        try {
            if (count($logs) > 0) {
                $contract = Contract::findOrFail($logs[0]['contract_id']);
                $this->temp_log_arr = array();
                $this->agreement_id_arr = array();
                $this->date_range_with_start_end_arr = array();
                $this->max_hours = 0.00;
                $this->week_count = 0.00;
                $this->admin_hours = 0.00;
                foreach ($logs as $log) {
                    if ($log['action_category'] == 12) { // Total clinical hours category actions need to avoid from calculation.
                        continue;
                    }
                    if ($log["current_user_status"] === 'Waiting') {
                        $this->flag_approve = true;
                        $logduration = $log['duration'];

                        foreach ($ratesArray[ContractRate::FMV_RATE][$log['contract_id']] as $rates) {
                            if (strtotime($rates['start_date']) <= strtotime($log['log_date']) && strtotime($rates['end_date']) >= strtotime($log['log_date'])) {
                                $this->rate = $rates['rate'];
                            }
                        }

                        $logDate = strtotime($log['log_date']);
                        $log_month = date("F", $logDate);
                        $log_year = date("Y", $logDate);
                        $log_date = $log['contract_id'].'-'.$log_month.'-'.$log_year;

                        if (array_key_exists($log["agreement_id"], $this->date_range_with_start_end_arr)) {
                            // do something.
                        } else {
                            $agreement_data = Agreement::getAgreementData($log["agreement_id"]);
                            $payment_type_factory = new PaymentFrequencyFactoryClass();
                            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                            $date_range_arr = $res_pay_frequency['date_range_arr'];
                            $this->date_range_with_start_end_arr[$log["agreement_id"]] = $res_pay_frequency['date_range_with_start_end_date'];
                        }

                        if (array_key_exists($log["agreement_id"], $this->date_range_with_start_end_arr)) {
                            foreach ($this->date_range_with_start_end_arr as $agreement_id => $dates_arr) {
                                foreach ($dates_arr as $range_index => $date_arr) {
                                    $temp_start_date = strtotime($date_arr['start_date']);
                                    $temp_end_date = strtotime($date_arr['end_date']);
                                    $check_log_date = strtotime($log["log_date"]);
                                    if ($check_log_date >= $temp_start_date && $check_log_date <= $temp_end_date) {
                                        $log_range_date = $log['contract_id'].'-'.$log['agreement_id'].'-'.$range_index;
                                        $max_hour_value = DB::table("rehab_max_hours_per_week")
                                            ->where('contract_id', '=', $log['contract_id'])
                                            ->where('start_date', '=', mysql_date(date("Y-m-d", $temp_start_date)))
                                            ->where('end_date', '=', mysql_date(date("Y-m-d", $temp_end_date)))
                                            ->value('max_hours_per_week');

                                        if ($max_hour_value) {
                                            $this->max_hours = $max_hour_value;
                                        }

                                        $week_count = DB::table("rehab_days_week_calculation")
                                            ->where('start_date', '=', mysql_date(date("Y-m-d", $temp_start_date)))
                                            ->value('week_count');

                                        if ($week_count) {
                                            $this->week_count = $week_count;
                                        }

                                        $admin_hours = DB::table("rehab_admin_hours")
                                            ->where('contract_id', '=', $log['contract_id'])
                                            ->where('start_date', '=', mysql_date(date("Y-m-d", $temp_start_date)))
                                            ->value('admin_hours');

                                        if ($admin_hours) {
                                            $this->admin_hours = $admin_hours;
                                        }


                                    }
                                    // log::info('$range_index', array($date_arr['start_date']));
                                }
                            }
                        }


                        /**
                         * Below condition is used for calculating worked_hours and expected_payment based on unique month
                         */

                        if ($log['action_category'] != 12) { // Total clinical hours category actions need to avoid from calculation.
                            if (in_array($log_range_date, $this->unique_date_range_arr)) {
                                $this->worked_hours_arr[$log_range_date] += $logduration;
                            } else {
                                array_push($this->unique_date_range_arr, $log_range_date);
                                $this->expected_payment += $log['expected_hours'] * $this->rate;
                                $this->worked_hours_arr[$log_range_date] = 0.00;
                                $this->worked_hours_arr[$log_range_date] += $logduration;
                                // Log::Info("month - $key", array($this->>unique_date_range_arr));
                            }
                        }

                        $this->hours += $logduration;
                        // $this->calculated_rehab_payment += $logduration * $this->rate;
                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                }
                if (count($this->worked_hours_arr) > 0) {
                    foreach ($this->worked_hours_arr as $month => $duration) {
                        if ($this->max_hours != 0.00 && $duration >= ($this->max_hours * $this->week_count)) {
                            $this->calculated_rehab_payment += (($this->week_count * $this->max_hours) + $this->admin_hours) * $this->rate;
                        } else {
                            $this->calculated_rehab_payment += ($duration + $this->admin_hours) * $this->rate;
                        }
                    }
                }

                // log::info(' $this->calculated_rehab_payment', array( $this->calculated_rehab_payment));

                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    'calculated_payment' => $this->calculated_rehab_payment,
                    'flagApprove' => $this->flag_approve,
                    'flagReject' => $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from Hourly::calculatedPayment : logs array cannot be empty.');
            }
        } catch (\Exception $ex) {
            Log::info("calculatePayment Hourly :".$ex->getMessage());
        }
    }
}