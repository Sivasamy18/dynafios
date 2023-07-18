<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\PhysicianLog;

class ComplianceDashboardRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliancedashboard:compliancedashboardrefresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Data for Compliance Dashboard';

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
        $results = PhysicianLog::updateTotalAndRejectedLogs($hospital_id = 0);

        return Command::SUCCESS;
    }
}
