<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\LogApproval;
use App\Agreement;
use App\Contract;
use App\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HospitalDashboardRefreshListOfContractSpecifics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospitaldashboard:listofcontractspecifics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Cache Data for Hospital Dashboard List Of Contract Specifics';

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
        $cache_seconds = 500; //5 minutes

        $users = User::select('users.*')
            ->whereNull('deleted_at')
            ->get();

        // $user_id = 1543; //8420;
        $user_contract_info = array();

        foreach($users as $user){
            $contracts_data = array();
            $hospitals_data = array();
            $region_ids = array();
            $hospital_ids = array();
            $agreement_ids = array();
            $region_hospital_ids = array();
            $hospital_agreement_ids = array();
            $region_id = 0;
            $hospital_id = 0;
            $today = date('Y-m-d');
       
            $user_id = $user->id;
            $group_id = $user->group_id;

            $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id);

            if($group_id == 2 && $group_id != 5){   // if(is_approval_manager() && !(is_super_hospital_user())){
                $allAgreements = Agreement::select("agreements.*","hospitals.name as hospital_name")
                    ->join('agreement_approval_managers_info','agreement_approval_managers_info.agreement_id','=','agreements.id')
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted=0")
                    // ->whereRaw("agreements.start_date <= now()")
                    // ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.start_date", '<=', mysql_date($today))
                    ->where("agreements.end_date", '>=', mysql_date($today))
                    // ->where("agreements.hospital_id", "<>", 45)
                    //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                    ->whereIn("agreement_approval_managers_info.user_id",$proxy_check_id) //added this condition for checking with proxy approvers
                    ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                    ->orderBy("agreements.hospital_id")
                    ->orderBy("agreements.id")
                    ->distinct()->get();
                }elseif($group_id == 7){       // }elseif(is_health_system_user()){
                    $allAgreements = Agreement::select("agreements.*","hospitals.name as hospital_name","health_system_regions.id as region_id","health_system_regions.region_name as region_name")
                        ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                        ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                        ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id")
                        ->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                        ->whereRaw("agreements.is_deleted=0")
                        // ->whereRaw("agreements.start_date <= now()")
                        // ->whereRaw("agreements.end_date >= now()")
                        ->where("agreements.start_date", '<=', mysql_date($today))
                        ->where("agreements.end_date", '>=', mysql_date($today))
                        // ->where("agreements.hospital_id", "<>", 45)
                        ->where("health_system_users.user_id", "=", $user_id)
                        ->whereNull('region_hospitals.deleted_at')
                        ->whereNull('health_system_regions.deleted_at')
                        ->whereNull('health_system_users.deleted_at')
                        ->orderBy("health_system_regions.id")
                        ->orderBy("agreements.hospital_id")
                        ->orderBy("agreements.id")
                        ->distinct()->get();
                }elseif($group_id == 8){    // }elseif(is_health_system_region_user()){
                    $allAgreements = Agreement::select("agreements.*","hospitals.name as hospital_name")
                        ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                        ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                        ->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                        ->whereRaw("agreements.is_deleted=0")
                        // ->whereRaw("agreements.start_date <= now()")
                        // ->whereRaw("agreements.end_date >= now()")
                        ->where("agreements.start_date", '<=', mysql_date($today))
                        ->where("agreements.end_date", '>=', mysql_date($today))
                        // ->where("agreements.hospital_id", "<>", 45)
                        ->where("health_system_region_users.user_id", "=", $user_id)
                        ->whereNull('region_hospitals.deleted_at')
                        ->whereNull('health_system_region_users.deleted_at')
                        ->orderBy("agreements.hospital_id")
                        ->orderBy("agreements.id")
                        ->distinct()->get();
            }else{
                $allAgreements = Agreement::select("agreements.*","hospitals.name as hospital_name")
                    ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted=0")
                    // ->whereRaw("agreements.start_date <= now()")
                    // ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.start_date", '<=', mysql_date($today))
                    ->where("agreements.end_date", '>=', mysql_date($today))
                    // ->where("agreements.hospital_id", "<>", 45)
                    //->where("hospital_user.user_id", "=", $user_id)
                    ->whereIn('hospital_user.user_id',$proxy_check_id) //added this condition for checking with proxy approvers
                    ->orderBy("agreements.hospital_id")
                    ->orderBy("agreements.id")
                    ->distinct()->get();
            }

            foreach($allAgreements as $agreement){
                if($group_id == 7){    // if(is_health_system_user()){
                    if ($region_id != $agreement->region_id) {
                        $region_ids[] = ["region_id" => $agreement->region_id,
                            "region_name" => $agreement->region_name];
                        if ($region_id != 0) {
                            $region_hospital_ids[$region_id] = $hospital_ids;
                            /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                            unset($hospital_ids);
                            $hospital_ids = array();
                        }
                        $region_id = $agreement->region_id;
                    }
    
                    if ($hospital_id != $agreement->hospital_id) {
                        $hospital_ids[] = ["hospital_id" => $agreement->hospital_id,
                            "hospital_name" => $agreement->hospital_name];
                        if ($hospital_id != 0) {
                            $hospital_agreement_ids[$hospital_id] = $agreement_ids;
                            /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                            unset($agreement_ids);
                            $agreement_ids = array();
                        }
                        $hospital_id = $agreement->hospital_id;
                    }
                    $agreement_ids[] = $agreement->id;
                }else {
                    if ($hospital_id != $agreement->hospital_id) {
                        $hospital_ids[] = ["hospital_id" => $agreement->hospital_id,
                            "hospital_name" => $agreement->hospital_name];
                        if ($hospital_id != 0) {
                            $hospital_agreement_ids[$hospital_id] = $agreement_ids;
                            /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                            unset($agreement_ids);
                            $agreement_ids = array();
                        }
                        $hospital_id = $agreement->hospital_id;
                    }
                    $agreement_ids[] = $agreement->id;
                }
            }

            if($group_id == 7){    // if(is_health_system_user()){
                $region_hospital_ids[$region_id]=$hospital_ids;
            }
    
            $hospital_agreement_ids[$hospital_id]=$agreement_ids;

            if($group_id == 7){     // if(is_health_system_user()){
                foreach ($region_ids as $region) {
                    foreach ($region_hospital_ids[$region["region_id"]] as $hospital) {
    //                    $practice_info = Contract::get_agreements_contract_info($hospital_agreement_ids[$hospital["hospital_id"]]);
                        $practice_info = [];
                        $hospitals_data[] = [
                            'hospital_id' => $hospital["hospital_id"],
                            'hospital_name' => $hospital["hospital_name"],
                            'contracts_info' => $practice_info
                        ];
                    }
                    $contracts_data[] = [
                        'region_name' => $region["region_name"],
                        'hospitals_info' => $hospitals_data
                    ];
                    unset($hospitals_data);
                    $hospitals_data = array();
                }
            }else {
                foreach ($hospital_ids as $hospital) {
                    if($group_id == 8){     // if(is_health_system_region_user()){
                        $practice_info = [];
                    }else {
                        $practice_info = Contract::get_agreements_contract_info($hospital_agreement_ids[$hospital["hospital_id"]]);
                    }
                    $contracts_data[] = [
                        'hospital_id' => $hospital["hospital_id"],
                        'hospital_name' => $hospital["hospital_name"],
                        'contracts_info' => $practice_info
                    ];
                }
            }

            $user_contract_info[] = [
                "user_id" => $user_id,
                "contracts_data" => $contracts_data
            ];
        }

        $contracts_data_result = json_encode($user_contract_info);

        Cache::put('agreements_data_cached', $contracts_data_result, $cache_seconds);

        return Command::SUCCESS;
    }
}
