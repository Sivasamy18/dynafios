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

class UpdateLogCounts implements ShouldQueue
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
        $this->hospital_id = $hospital_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        PhysicianLog::updateTotalAndRejectedLogs($this->hospital_id);
    }
}
