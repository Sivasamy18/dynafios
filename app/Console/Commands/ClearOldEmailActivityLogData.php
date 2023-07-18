<?php

namespace App\Console\Commands;

use App\Models\MailTracker;
use Illuminate\Console\Command;

class ClearOldEmailActivityLogData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:clear-old-email-activity-logs {days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all records in the email tracking database table older than X days, defaults to 30.';

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
        $days = $this->argument('days');

        $this->info("Deleting all email activity logs older than {$days} days...");
        // Delete all email activity logs older than X days
        MailTracker::where('created_at', '<', now()->subDays($days))->delete();
        return self::SUCCESS;
    }
}
