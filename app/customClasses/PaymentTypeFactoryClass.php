<?php
namespace App\customClasses;

use App\customClasses\HourlyPaymentCalculation;
use App\customClasses\PerDiemPaymentCalculation;
use App\customClasses\PsaPaymentCalculation;
use App\customClasses\StipendPaymentCalculation;
use App\customClasses\MonthlyStipendPaymentCalculation;
use App\customClasses\PerDiemUncompensatedPaymentCalculation;
use App\customClasses\RehabPaymentCalculation;
use App\PaymentType;
use Illuminate\Support\Facades\Log;

class PaymentTypeFactoryClass {

    protected $PaymentTypeClass;
    // Determine which model to manufacture, and instantiate
    //  the concrete classes that make each model.
    public function getPaymentCalculationClass($payment_type=null)
    {
        if($payment_type !=null){
            if($payment_type == PaymentType::STIPEND || $payment_type == PaymentType::TIME_STUDY){
                return $this->PaymentTypeClass = new StipendPaymentCalculation();
            }
            if($payment_type == PaymentType::HOURLY || $payment_type == PaymentType::PER_UNIT){
                return $this->PaymentTypeClass = new HourlyPaymentCalculation();
            }
            if($payment_type == PaymentType::PER_DIEM){
                return $this->PaymentTypeClass = new PerDiemPaymentCalculation();
            }
            if($payment_type == PaymentType::PSA){
                return $this->PaymentTypeClass = new PsaPaymentCalculation();
            }
			if($payment_type == PaymentType::MONTHLY_STIPEND)
			{
				return $this->PaymentTypeClass = new MonthlyStipendPaymentCalculation();
			}
            if($payment_type == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS){
                return $this->PaymentTypeClass = new PerDiemUncompensatedPaymentCalculation();
            }
            if($payment_type == PaymentType::REHAB){
                return $this->PaymentTypeClass = new RehabPaymentCalculation();
            }
        } else {
            Log::Info('From PaymentTypeFactoryClass method make(): please provide payment_type');
        }
    }
}
