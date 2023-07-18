<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\PhysicianLog;
use App\Physician;
use App\Contract;
use Mail;
use Illuminate\Support\Facades\Log;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;

class ApproverPendingLogEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendemail:approverpendinglog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email to Approver';

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
        $log_details = [];
        $approvers = PhysicianLog::select('physician_logs.next_approver_user as approver', 'users.first_name as approver_name', 'users.email as approver_email')
            ->join('users', 'users.id', '=', 'physician_logs.next_approver_user')
            ->where('physician_logs.next_approver_user', '>', 0)
            // ->where('physician_logs.next_approver_user', '=', 8456)
            ->whereNull('physician_logs.deleted_at')
            ->distinct()
            ->get();

        foreach($approvers as $approver){
            $contracts = PhysicianLog::select('contracts.id as contract_id', 'contract_names.name as contract_name', 'physicians.id as physician_id', 'physicians.first_name as physician_first_name', 'physicians.last_name as physician_last_name')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
				->where('physician_logs.signature', '=', 0)
				->where('physician_logs.approval_date', '=', '0000-00-00')
				->where('physician_logs.approved_by', '=', 0)
				->where('physician_logs.next_approver_user', '=', $approver->approver)
                ->where('contracts.archived', '=', false)
                ->where('contracts.manually_archived', '=', false)
                ->whereNull('contracts.deleted_at')
                // ->whereNull('agreements.deleted_at')
                ->whereNull('physicians.deleted_at')
				->distinct()
                ->get();

            $log_details = [];
			foreach($contracts as $contract){
				$logs = PhysicianLog::select(DB::raw('physician_logs.date as log_date, YEAR(physician_logs.date) as year, MONTH(physician_logs.date) as month, MONTHNAME(physician_logs.date) as month_name'))
					->where('physician_logs.signature', '=', 0)
					->where('physician_logs.approval_date', '=', '0000-00-00')
					->where('physician_logs.approved_by', '=', 0)
					->where('physician_logs.contract_id', '=', $contract->contract_id)
					->where('physician_logs.next_approver_user', '=', $approver->approver)
					->groupBy('month')
					->groupBy('year')
					->orderBy('year', 'asc')
					->orderBy('month', 'asc')
					->get();

				if(count($logs) > 0){
					foreach($logs as $log){
						$log_details[] = [
							'physician_name' => $contract->physician_first_name .' '. $contract->physician_last_name,
							'contract_name' => $contract->contract_name,
							'period' => $log->month_name .' '. $log->year
						];
					}
				}
			}
            if(count($log_details) > 0){
                $data = [
                    'name' => $approver->approver_name,
                    'email' => $approver->approver_email,
                    'type' => EmailSetup::NEXT_APPROVER_PENDING_LOG,
                    'with' => [
                        'name' => $approver->approver_name,
                        'log_details' => $log_details
                    ]
                ];
    
                try {
                    EmailQueueService::sendEmail($data);
                }
                catch (Exception $e) {
                    log::error('Error Approver Pending Log Monday Morning Email: ' . $e->getMessage());
                }
            }
        }

        return Command::SUCCESS;;
    }
}
