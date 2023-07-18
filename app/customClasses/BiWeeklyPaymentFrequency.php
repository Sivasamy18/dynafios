<?php
namespace App\customClasses;

use Illuminate\Support\Facades\Log;

class BiWeeklyPaymentFrequency extends PaymentFrequencyCalculation
{
    /**
     * @param $agreement_data
     * @return array
     *
     * Below method is used for calculating the payment frequency date range for bi-weekly contract types logs.
     * It requires agreement_data as parameter for calculation.
     */
    protected function getDateRange($agreement_data) {
        try{
            
            $this->temp_arr_final = array();
            $this->today = date('Y-m-d');
            // $this->frequency_start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
            $this->frequency_start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->payment_frequency_start_date)));
            $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
            $this->prior_date = $prior_month_end_date;
            $this->month_wise_start_end_date = array();
            
            $date = strtotime($this->frequency_start_date);
            $date = strtotime("-1 day", $date);

            if($date <= strtotime($this->today)){
                while($date <= strtotime($this->today)){
                    $date = strtotime("+14 day", $date);
                    array_push($this->temp_date_range_arr, date('Y-m-d', $date));
                }

                $existing_month_arr = [];
                foreach($this->temp_date_range_arr as $end_date){
                    $this->temp_arr = array();
                    $date_val = strtotime($end_date);
                    $start_date = strtotime("-13 day", $date_val);
    
                    if($date_val < strtotime($this->today)){
                        $this->prior_date = date('Y-m-d', $date_val);
                    }
                    
                    if(strtotime($this->today) < $start_date && strtotime($this->today) > $date_val){
                        break;    
                    }
                    $start_date = date('Y-m-d', $start_date);
                    $this->temp_arr['start_date'] = $start_date;
                    $this->temp_arr['end_date'] = $end_date;
                    array_push($this->temp_arr_final, $this->temp_arr);

                    /*
                     * Below code is for getting the each month start and end date till prior month.
                     */
                    $this->temp_month_arr = array();
                    $current_month_start_date = date('Y-m-01', strtotime($start_date));
                    $current_month_end_date = date('Y-m-t', strtotime($start_date));
                    $this->temp_month_arr['start_date'] = $current_month_start_date;
                    $this->temp_month_arr['end_date'] = $current_month_end_date;

                    if(!in_array($current_month_start_date, $existing_month_arr)){
                        array_push($this->month_wise_start_end_date, $this->temp_month_arr);
                        array_push($existing_month_arr, $current_month_start_date);
                    }
                }
            } else {
                // This is when frequency start date is in future date. Set the result with the frequency date itself.
                $this->prior_date= $this->frequency_start_date;
                $this->temp_arr = array();
                $this->temp_month_arr = array();
                array_push($this->temp_date_range_arr, $this->frequency_start_date);
                $this->temp_arr['start_date'] = $this->frequency_start_date;
                $this->temp_arr['end_date'] = $this->frequency_start_date;
                array_push($this->temp_arr_final, $this->temp_arr);
                $this->temp_month_arr['start_date'] = $this->frequency_start_date;
                $this->temp_month_arr['end_date'] = $this->frequency_start_date;
                array_push($this->month_wise_start_end_date, $this->temp_month_arr);
            }

            // log::info('$prior_date', array($this->prior_date));
            // log::info('$temp_arr_final', array($this->temp_arr_final));

            return [
                'prior_date' => $this->prior_date,
                'date_range_arr' => $this->temp_date_range_arr,
                'date_range_with_start_end_date' => $this->temp_arr_final,
                'month_wise_start_end_date' => $this->month_wise_start_end_date
            ];

        }catch (\Exception $ex){
            Log::info("calculateDateRange BiWeekly :" . $ex->getMessage());
        }
    }
}