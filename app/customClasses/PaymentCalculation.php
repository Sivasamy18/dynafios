<?php


namespace App\customClasses;


abstract class PaymentCalculation
{
        protected $hours = 0;
        protected $rate = 0;
        protected $calculated_payment = 0.0;
        protected $flag_approve = false;
        protected $flagReject =  false;
        protected $temp_log_arr = array();
        protected $unique_date_range_arr = array();
        protected $expected_payment = 0.0;

        protected $worked_hours_arr = array();
        protected $calculated_monthly_pmt_arr = array();
        protected $calculated_stipend_payment = 0.0;
        protected $calculated_expected_hours = 0;

        protected $calculated_hourly_payment = 0.0;

        protected $calculated_psa_payment = 0.0;

    protected $calculated_rehab_payment = 0.0;
    abstract public function calculatePayment($logs, $ratesArray);
}