<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\HealthSystem;
use App\Group;
use App\HealthSystemUsers;
use App\HealthSystemRegion;
use App\RegionHospitals;
use App\HealthSystemReport;
use App\PaymentType;
use App\ContractType;
use App\HealthSystemRegionUsers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class HealthSystemDashRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'healthsystem:regendash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Cache Data for Health System';

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
        //This code is also run in HealthSystemController.php
        $cache_seconds = 300; //3 minutes


    
        //Query Specification: https://bi.dynafios.com/question/307-5-9-5-feature-health-system-dashboard-v1/notebook
        $results = DB::table('health_system')
            ->selectRaw('health_system.id as id, count(*) as count')
            ->leftJoin('health_system_regions', 'health_system.id', '=', 'health_system_regions.health_system_id')
            ->leftJoin('region_hospitals', 'health_system_regions.id', '=', 'region_hospitals.region_id')
            ->leftJoin('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->whereNull('health_system.deleted_at')
            ->whereNull('health_system_regions.deleted_at')
            ->whereNull('region_hospitals.deleted_at')
            ->whereNull('hospitals.deleted_at')
            ->groupBy('health_system.id')
            ->orderBy('health_system.id')
            ->get();


        $new_results = [];
        foreach ($results as $row) {
            $data = HealthSystem::getDetails($row->id);
            $count = ($data['system']['total_users_count']);
            $row->total_users = $count;
            $new_results[] = $row;
        }


        $count_result = json_encode($new_results);



        Cache::put('facility_info_cached', $count_result, $cache_seconds);

        return Command::SUCCESS;
    }
}
