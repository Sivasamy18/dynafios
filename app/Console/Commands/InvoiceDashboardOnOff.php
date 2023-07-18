<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Agreement;
use Illuminate\Support\Facades\Log;

class InvoiceDashboardOnOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoicedashboard:invoicedashboardonoff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoice Dashboard job status monthly';

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
        $results = Agreement::invoiceDashboardOnOff();

        return Command::SUCCESS;
    }
}
