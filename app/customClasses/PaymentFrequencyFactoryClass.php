<?php
namespace App\customClasses;

use App\customClasses\MonthlyPaymentFrequency;
use App\customClasses\WeeklyPaymentFrequency;
use App\customClasses\QuarterlyPaymentFrequency;
use App\customClasses\BiWeeklyPaymentFrequency;
use App\Agreement;
use Illuminate\Support\Facades\Log;

class PaymentFrequencyFactoryClass {

    protected $PaymentFrequencyClass;
    // Determine which model to manufacture, and instantiate
    //  the concrete classes that make each model.
    public function getPaymentFactoryClass($agreement_frequency_type=null)
    {
        if($agreement_frequency_type !=null){
            if($agreement_frequency_type == Agreement::MONTHLY){
                return $this->PaymentFrequencyClass = new MonthlyPaymentFrequency();
            }
            if($agreement_frequency_type == Agreement::WEEKLY){
                return $this->PaymentFrequencyClass = new WeeklyPaymentFrequency();
            }
            if($agreement_frequency_type == Agreement::BI_WEEKLY){
                return $this->PaymentFrequencyClass = new BiWeeklyPaymentFrequency();
            }
            if($agreement_frequency_type == Agreement::QUARTERLY){
                return $this->PaymentFrequencyClass = new QuarterlyPaymentFrequency();
            }
        } else {
            Log::Info('From getPaymentFactoryClass method make(): please provide agreement_frequency_type');
        }
    }
}
