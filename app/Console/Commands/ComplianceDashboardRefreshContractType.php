<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ContractType;
use App\Agreement;
use App\PhysicianLog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComplianceDashboardRefreshContractType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliancedashboard:rejectionrateofcontracttype';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Cache Data for Compliance Dashboard Rejection Rate – Contract Type';

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
        $cache_seconds = 300; //3 minutes
        $user_id = 8420;
        $facility = 0;

        $query = ContractType::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $sorted_array = array();
        $return_data = array();

        foreach ($contract_types as $contract_type) {
            $contract_count = ContractType::getContractsType($contract_type->id, $user_id, $facility);

            if (count($contract_count) > 0 ) {
                $total_logs = 0;
                $rejected_logs = 0;

                foreach($contract_count as $contract){
                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

                    $logs = PhysicianLog::select("physician_logs.*")
                        ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->where("contracts.contract_type_id", "=", $contract_type->id)
                        ->where("contracts.id", $contract->id)
                        ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                        ->whereNull("physician_logs.deleted_at")
                        ->count();

                    $rejection_log = PhysicianLog::select("physician_logs.*")
                        ->join("log_approval","log_approval.log_id", "=", "physician_logs.id")
                        // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                        ->join("physicians","physicians.id", "=", "physician_logs.physician_id")
                        ->join("contracts", "contracts.id", "=", "physician_logs.contract_id")
                        ->where("log_approval.role", "=","1")
                        ->where("log_approval.approval_status", "=","0")
                        // ->where("log_approval_history.approval_status", "=","0")
                        ->where("contracts.contract_type_id", "=", $contract_type->id)
                        ->where("contracts.id", $contract->id)
                        ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                        ->whereNull("physician_logs.deleted_at")
                        ->count();   
                        
                    $total_logs += $logs;
                    $rejected_logs += $rejection_log;

                    if($rejected_logs > 0){
                        $return_data[] = [
                            "contract_type_id" => $contract_type->id,
                            "contract_type_name" => $contract_type->name,
                            "rejection_count" => $rejected_logs,
                            "logs_count" => $total_logs
                        ];
                    }
                }
            }
        }

        $result = json_encode($return_data);

        Cache::put('contracts_type_data_cached', $result, $cache_seconds);

        return Command::SUCCESS;
    }
}
