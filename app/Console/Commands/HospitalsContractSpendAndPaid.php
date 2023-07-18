<?php

namespace App\Console\Commands;

use App\Hospital;
use Illuminate\Console\Command;

class HospitalsContractSpendAndPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospitaldashboard:hospitalscontractspendandpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hospitals contract spend and paid job status daily';

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
        $results = Hospital::postHospitalsContractTotalSpendAndPaid($hospital_id = 0);

        return Command::SUCCESS;
    }
}
