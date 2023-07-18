<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\HospitalContractSpendPaid;
use Illuminate\Support\Facades\Log;

class UpdatePendingPaymentCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $hospital_id = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($hospital_id)
    {
//        Log::info("UpdatePendingPaymentCount construct", array($hospital_id));
        $this->hospital_id = $hospital_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        HospitalContractSpendPaid::updatePendingPaymentCount($this->hospital_id);
    }
}
