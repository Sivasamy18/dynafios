<?php


namespace App\customClasses;


abstract class PaymentFrequencyCalculation
{
        protected $agreement_start_date = '0000-00-00';
        protected $prior_date = '0000-00-00';
        protected $today = '0000-00-00';
        protected $temp_date_range_arr = array();
        protected $temp_date_range_obj = array();

        public function calculateDateRange($agreement_data, $from_date = NULL, $to_date = NULL) {
            $dateObject = $this->getDateRange($agreement_data);
            if (!empty($from_date) && !empty($to_date)) {
                //take out extra dates here
                foreach($dateObject['date_range_with_start_end_date'] as $res_key => $result_data_obj){
                    if($result_data_obj['end_date'] < $from_date || $result_data_obj['start_date'] > $to_date){
                        unset($dateObject['date_range_with_start_end_date'][$res_key]);
                    } else {
                        $this->temp_date_range_obj[] = $result_data_obj;
                    }
                }
                $dateObject['date_range_with_start_end_date'] = $this->temp_date_range_obj;
            }
            return $dateObject;
        }

        protected abstract function getDateRange($agreement_data);
}