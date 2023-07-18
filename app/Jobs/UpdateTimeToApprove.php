<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\PhysicianLog;
use Log;

class UpdateTimeToApprove implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $agreement_id = null;
    private $log_id = null;
    private $level = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($agreement_id, $log_id, $level)
    {
        $this->agreement_id = $agreement_id;
        $this->log_id = $log_id;
        $this->level = $level;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        PhysicianLog::update_time_to_approve($this->agreement_id, $this->log_id, $this->level);
    }
}
