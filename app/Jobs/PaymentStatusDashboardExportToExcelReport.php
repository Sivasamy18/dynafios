<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\PaymentStatusDashboard;
use Log;

class PaymentStatusDashboardExportToExcelReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user_id = 0;
    private $selected_manager = 0;
    private $payment_type = 0;
    private $contract_type = 0;
    private $selected_hospital = 0;
    private $selected_agreement = 0;
    private $selected_practice = 0;
    private $selected_physician = 0;
    private $start_date = "";
    private $end_date = "";
    private $report_type = 0;
    private $status = 0;
    private $show_calculated_payment = false;
    private $group_id = 0;
    private $timestamp = 0;
    private $timeZone = 0;
    private $approver = 0;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, $report_type, $status, $show_calculated_payment, $group_id, $timestamp, $timeZone, $approver)
    {
        $this->user_id = $user_id;
        $this->selected_manager = $selected_manager;
        $this->payment_type = $payment_type;
        $this->contract_type = $contract_type;
        $this->selected_hospital = $selected_hospital;
        $this->selected_agreement = $selected_agreement;
        $this->selected_practice = $selected_practice;
        $this->selected_physician = $selected_physician;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->report_type = $report_type;
        $this->status = $status;
        $this->show_calculated_payment = $show_calculated_payment;
        $this->group_id = $group_id;
        $this->timestamp = $timestamp;
        $this->timeZone = $timeZone;
        $this->approver = $approver;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        PaymentStatusDashboard::PaymentStatusDashboardReport($this->user_id, $this->selected_manager, $this->payment_type, $this->contract_type, $this->selected_hospital, $this->selected_agreement, $this->selected_practice, $this->selected_physician, $this->start_date, $this->end_date, $this->report_type, $this->status, $this->show_calculated_payment, $this->group_id, $this->timestamp, $this->timeZone, $this->approver);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        // Called when the job is failing...
        log::info('Job failed PaymentStatusDashboardExportToExcelReport !');
    }
}
