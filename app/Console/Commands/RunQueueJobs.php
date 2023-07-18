<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdatePendingPaymentCount;
use Log;

class RunQueueJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queuerun:jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::Info("RunQueueJobs command handle");
        UpdatePendingPaymentCount::dispatch(253);
        return Command::SUCCESS;
    }
}
