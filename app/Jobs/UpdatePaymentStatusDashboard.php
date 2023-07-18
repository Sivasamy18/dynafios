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

class UpdatePaymentStatusDashboard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $physician_id = 0;
    private $practice_id = 0;
    private $contract_id = 0;
    private $contract_name_id = 0;
    private $hospital_id = 0;
    private $agreement_id = 0;
    private $selected_date = "";

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($physician_id, $practice_id, $contract_id, $contract_name_id, $hospital_id, $agreement_id, $selected_date)
    {
        // log::info('handle param Test construct-->', array($physician_id, $practice_id, $contract_id, $contract_name_id, $hospital_id, $agreement_id, $selected_date));
        $this->physician_id = $physician_id;
        $this->practice_id = $practice_id;
        $this->contract_id = $contract_id;
        $this->contract_name_id = $contract_name_id;
        $this->hospital_id = $hospital_id;
        $this->agreement_id = $agreement_id;
        $this->selected_date = $selected_date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // log::info('handle param Test handle --->');
        PaymentStatusDashboard::updatePaymentStatusDashboard($this->physician_id, $this->practice_id, $this->contract_id, $this->contract_name_id, $this->hospital_id, $this->agreement_id, $this->selected_date);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        // Called when the job is failing...
        log::info('Job failed UpdatePaymentStatusDashboard !');
    }
}
