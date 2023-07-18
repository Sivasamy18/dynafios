<?php

namespace App\customClasses;

use App\Agreement;
use App\Amount_paid;
use App\ContractRate;
use App\PhysicianLog;
use Illuminate\Support\Facades\Log;

class StipendPaymentCalculation extends PaymentCalculation
{
    /**
     * @param $logs
     * @param $ratesArray
     * @return array
     *
     * Below method is used for calculating the payment for stipend contract types logs.
     * It requires Logs and its ratesArray as parameter for calculation.
     */
    public function calculatePayment($logs, $ratesArray)
    {
        try {
            // log::info('$logs', array($logs));
            if (count($logs) > 0) {
                $this->rate = 0;
                $this->agreement_id_arr = array();
                $this->date_range_with_start_end_arr = array();
                $this->agreement_id = 0;
                $this->contract_id = 0;
                $this->practice_id = 0;
                $this->res_pay_frequency = array();
                $this->month_logs = array();
                foreach ($logs as $key => $log) {

                    if ($key == 0) {
                        $this->agreement_id = $log["agreement_id"];
                        $this->contract_id = $log["contract_id"];
                        $this->practice_id = $log["practice_id"];
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
                        $log_date = $log['contract_id'] . '-' . $log_month . '-' . $log_year;

                        if (array_key_exists($log["agreement_id"], $this->date_range_with_start_end_arr)) {
                            // do something.
                        } else {

                            if (count($this->res_pay_frequency) == 0) {
                                $agreement_data = Agreement::getAgreementData($log["agreement_id"]);
                                $payment_type_factory = new PaymentFrequencyFactoryClass();
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                                $this->res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                            }
                            $this->date_range_with_start_end_arr[$log["agreement_id"]] = $this->res_pay_frequency['date_range_with_start_end_date'];
                        }

                        if (array_key_exists($log["agreement_id"], $this->date_range_with_start_end_arr)) {
                            foreach ($this->date_range_with_start_end_arr as $agreement_id => $dates_arr) {
                                foreach ($dates_arr as $range_index => $date_arr) {
                                    $temp_start_date = strtotime($date_arr['start_date']);
                                    $temp_end_date = strtotime($date_arr['end_date']);
                                    $check_log_date = strtotime($log["log_date"]);
                                    if ($check_log_date >= $temp_start_date && $check_log_date <= $temp_end_date) {
                                        $log_range_date = $log['contract_id'] . '-' . $log['agreement_id'] . '-' . $range_index;
                                        $this->month_logs = $date_arr;
                                    }
                                    // log::info('$range_index', array($date_arr['start_date']));
                                }
                            }
                        }

                        /**
                         * Below condition is used for calculating worked_hours and expected_payment based on unique month
                         */
                        if (in_array($log_range_date, $this->unique_date_range_arr)) {
                            $this->worked_hours_arr[$log_range_date] += $logduration;
                        } else {
                            array_push($this->unique_date_range_arr, $log_range_date);
                            $this->expected_payment += $log['expected_hours'] * $this->rate;
                            $this->worked_hours_arr[$log_range_date] = 0.00;
                            $this->worked_hours_arr[$log_range_date] += $logduration;
                            // Log::Info("month - $key", array($this->>unique_date_range_arr));
                        }

                        if ($log['min_hours'] <= $this->worked_hours_arr[$log_range_date]) {
                            if (!array_key_exists($log_range_date, $this->calculated_monthly_pmt_arr)) {
                                $this->calculated_monthly_pmt_arr[$log_range_date] = $log['expected_hours'] * $this->rate;
                                $this->calculated_stipend_payment += $this->calculated_monthly_pmt_arr[$log_range_date];
                            }
                        }

                        $this->hours += $logduration;
                        array_push($this->temp_log_arr, $log['log_id']);
                    }
                }

                $today = date('Y-m-d');
                $agreement_start_date = Agreement::where('id', '=', $this->agreement_id)->value('start_date');
                $agreement_end_date = date('Y-m-d', strtotime("-1 day", strtotime("+1 year", strtotime($agreement_start_date))));

                while ($agreement_end_date < $this->month_logs["start_date"] && $agreement_start_date > $this->month_logs["start_date"]) {
                    $agreement_start_date = date('Y-m-d', strtotime("+1 year", strtotime($agreement_start_date)));
                    $agreement_end_date = date('Y-m-d', strtotime("-1 day", strtotime("+1 year", strtotime($agreement_start_date))));
                }

                $amount_paid_sum_cumulative = Amount_paid::where('contract_id', '=', $this->contract_id)
                    ->where('practice_id', '=', $this->practice_id)
                    ->where('start_date', '>=', $agreement_start_date)
                    ->where('end_date', '<=', $agreement_end_date)
                    ->sum('amountPaid');

                $log_hour_sum_cumulative = PhysicianLog::where('contract_id', '=', $this->contract_id)
                    ->where('practice_id', '=', $this->practice_id)
                    ->whereBetween("date", [$agreement_start_date, $agreement_end_date])
                    ->where('approval_date', '!=', '0000-00-00')
                    ->where('signature', '!=', 0)
                    ->sum('duration');

                if ($log_hour_sum_cumulative > 0) {
                    $cumulative_total_paid = $amount_paid_sum_cumulative / $log_hour_sum_cumulative;
                } else {
                    $cumulative_total_paid = $amount_paid_sum_cumulative;
                }

                if ($cumulative_total_paid >= $this->rate) {
                    $this->calculated_stipend_payment = 0.00;
                }

                return [
                    'hours' => $this->hours,
                    'log_ids' => $this->temp_log_arr,
                    'calculated_payment' => $this->calculated_stipend_payment,
                    'flagApprove' => $this->flag_approve,
                    'flagReject' => $this->flagReject,
                    'unique_date_range_arr' => $this->unique_date_range_arr,
                    'expected_payment' => $this->expected_payment
                ];
            } else {
                Log::Info('from Stipend::calculatedPayment : logs array cannot be empty.');
            }
        } catch (\Exception $ex) {
            Log::info("calculatePayment Stipend :" . $ex->getMessage());
        }
    }
}