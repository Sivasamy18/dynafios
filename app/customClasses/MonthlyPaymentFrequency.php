<?php
namespace App\customClasses;

use DateTime;
use Illuminate\Support\Facades\Log;

class MonthlyPaymentFrequency extends PaymentFrequencyCalculation
{
    /**
     * @param $agreement_data
     * @return array
     *
     * Below method is used for calculating the payment frequency date range for monthly contract types logs.
     * It requires agreement_data as parameter for calculation.
     */
    public function getDateRange($agreement_data) {
        try{
            
            $this->today = date('Y-m-d');
            // $this->frequency_start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
            $this->frequency_start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->payment_frequency_start_date)));
            // $prior_month_end_date = date('Y-m-d', strtotime("last day of previous month"));
            // $this->prior_date = $prior_month_end_date;


            $this->temp_arr_final = array();
            $this->month_wise_start_end_date = array();

            // $date = strtotime($this->frequency_start_date);
            $date = with(new DateTime($this->frequency_start_date));

            if($date <= new DateTime($this->today)){

                $existing_month_arr = [];
                while($date <= new DateTime($this->today) ){

                    $this->temp_arr = array();
    
                    $last_date = with(clone $date)->modify('+1 month')->modify('-1 day');
                    array_push($this->temp_date_range_arr, $last_date->format('Y-m-d'));
                    $this->temp_arr['start_date'] = $date->format('Y-m-d');
                    $this->temp_arr['end_date'] = $last_date->format('Y-m-d');
                    array_push($this->temp_arr_final, $this->temp_arr);
                    
                    if(strtotime($last_date->format('Y-m-d')) < strtotime($this->today)){
                        $this->prior_date = $last_date->format('Y-m-d');
                    }

                    /*
                     * Below code is for getting the each month start and end date till prior month.
                     */
                    $this->temp_month_arr = array();
                    $current_month_start_date = date('Y-m-01', strtotime($date->format('Y-m-d')));
                    $current_month_end_date = date('Y-m-t', strtotime(with(new DateTime($current_month_start_date))->modify('+1 month')->modify('-1 day')->format('Y-m-d')));
                    $this->temp_month_arr['start_date'] = $current_month_start_date;
                    $this->temp_month_arr['end_date'] = $current_month_end_date;

                    if(!in_array($current_month_start_date, $existing_month_arr)){
                        array_push($this->month_wise_start_end_date, $this->temp_month_arr);
                        array_push($existing_month_arr, $current_month_start_date);
                    }
                    
                    $date = $last_date->modify('+1 day')->setTime(0, 0, 0);
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

            return [
                'prior_date' => $this->prior_date,
                'date_range_arr' => $this->temp_date_range_arr,
                'date_range_with_start_end_date' => $this->temp_arr_final,
                'month_wise_start_end_date' => $this->month_wise_start_end_date
            ];
        }catch (\Exception $ex){
            Log::info("calculateDateRange Monthly :" . $ex->getMessage());
        }
    }
}