<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Hospital;
use App\Agreement;
use App\Contract;
use App\HealthSystemUsers;
use App\Practice;
use App\Physician;
use App\PhysicianLog;
use App\User;
use App\HealthSystemRegionUsers;
use App\HealthSystemRegion;
use App\RegionHospitals;
use App\Group;
use Illuminate\Support\Facades\Auth;
use Redirect;
use Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HospitalDashboardRefreshPieCharts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hospitaldashboard:piecharts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Cache Data for Hospital Dashboard Pie Charts';

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
        $cache_seconds = 500; // 5 minutes
        $today = date("Y-m-d");
        $user_pie_chart_data = array();

        $users = User::select('users.*')
            ->whereNull('deleted_at')
            ->get();

        foreach($users as $user){
            $user_id = $user->id;
            $group_id = $user->group_id;
            $password_expiration_date = $user->password_expiration_date;

            if ($password_expiration_date <= $today) {
                return Redirect::route('users.expired', $user_id);
            }

            if ($group_id == 2) {
                //$dashbord_stats = Hospital::fetch_contract_stats();
                $dashbord_stats = Hospital::fetch_contract_stats_using_union();
    
                $hospital_count_exclude_onee = Agreement::select(DB::raw("distinct(agreements.hospital_id) as hospital_id"))
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                     ->whereRaw("agreements.start_date <= now()")
                     ->whereRaw("agreements.end_date >= now()")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereRaw("hospitals.archived=0")
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('hospitals.deleted_at');

                $hospital_count_exclude_one = $hospital_count_exclude_onee->pluck('hospital_id','hospital_id')->toArray();
    
                $physician_count_exclude_onee = Contract::select(DB::raw("distinct(contracts.physician_id) as physician_id"))
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                     ->whereRaw("agreements.start_date <= now()")
                      ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('physicians.deleted_at');

                $physician_count_exclude_one = $physician_count_exclude_onee->pluck('physician_id','physician_id')->toArray();
    
                $contract_count_exclude_onee = Contract::select(DB::raw("contracts.id"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                     ->whereRaw("agreements.start_date <= now()")
                      ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->whereNull('contracts.deleted_at');
                    // ->where("agreements.hospital_id", "<>", 113);

                $contract_count_exclude_one = $contract_count_exclude_onee->get();
    
                $hospital_user_exclude_onee = DB::table("hospital_user")
                    ->select(DB::raw("distinct(concat(hospital_user.user_id,'-',hospital_user.hospital_id)) as user_id"))
                    ->join("users", "users.id", "=", "hospital_user.user_id")
                    ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                     ->whereRaw("agreements.start_date <= now()")
                     ->whereRaw("agreements.end_date >= now()")
                     ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $hospital_user_exclude_one = $hospital_user_exclude_onee->pluck('user_id','user_id')->toArray();
    
                $health_system_user_exclude_onee = HealthSystemUsers::select(DB::raw("distinct(concat(health_system_users.user_id,'-',health_system_users.health_system_id)) as user_id"))
                    ->join("users", "users.id", "=", "health_system_users.user_id")
                    ->join("health_system", "health_system.id", "=", "health_system_users.health_system_id")
                    ->whereNull('users.deleted_at')
                    ->whereNull('health_system.deleted_at');
                $health_system_user_exclude_one = $health_system_user_exclude_onee->pluck('user_id','user_id')->toArray();
    
                $health_system_region_user_exclude_onee = HealthSystemRegionUsers::select(DB::raw("distinct(concat(health_system_region_users.user_id,'-',health_system.id,'-',health_system_region_users.health_system_region_id)) as user_id"))
                    ->join("users", "users.id", "=", "health_system_region_users.user_id")
                    ->join("health_system_regions", "health_system_regions.id", "=", "health_system_region_users.health_system_region_id")
                    ->join("health_system", "health_system.id", "=", "health_system_regions.health_system_id")
                    ->whereNull('users.deleted_at')
                    ->whereNull('health_system.deleted_at')
                    ->whereNull('health_system_regions.deleted_at');
                $health_system_region_user_exclude_one = $health_system_region_user_exclude_onee->pluck('user_id','user_id')->toArray();
    
    
                $practice_user_count_exclude_onee = DB::table("practice_user")
                    ->select(DB::raw("distinct(concat(practice_user.user_id,'-',hospitals.id)) as user_id"))
                    ->join("users", "users.id", "=", "practice_user.user_id")
                    ->join("practices", "practices.id", "=", "practice_user.practice_id")
                    ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                      ->whereRaw("agreements.end_date >= now()")
                      ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $practice_user_count_exclude_one = $practice_user_count_exclude_onee->pluck('user_id','user_id')->toArray();
    
                $hospital_user_distinct_exclude_onee = DB::table("hospital_user")
                    ->select(DB::raw("distinct(hospital_user.user_id)"))
                    ->join("users", "users.id", "=", "hospital_user.user_id")
                    ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $hospital_user_distinct_exclude_one = $hospital_user_distinct_exclude_onee->get();
    
                $practice_user_count_distinct_exclude_onee = DB::table("practice_user")
                    ->select(DB::raw("distinct(practice_user.user_id)"))
                    ->join("users", "users.id", "=", "practice_user.user_id")
                    ->join("practices", "practices.id", "=", "practice_user.practice_id")
                    ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $practice_user_count_distinct_exclude_one = $practice_user_count_distinct_exclude_onee->get();
    
                $physician_pending_count_exclude_onee = Contract::select(DB::raw("distinct(contracts.physician_id) as physician_id"))
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date >= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('physicians.deleted_at');

                $physician_pending_count_exclude_one = $physician_pending_count_exclude_onee->pluck('physician_id','physician_id')->toArray();
                foreach (array_keys($physician_pending_count_exclude_one) as $rec) {
                    if (array_key_exists($rec, $physician_count_exclude_one)) {
                        unset($physician_pending_count_exclude_one[$rec]);
                    }
                }
    
                $hospital_user_pending_exclude_onee = DB::table("hospital_user")
                    ->select(DB::raw("distinct(concat(hospital_user.user_id,'-',hospital_user.hospital_id)) as user_id"))
                    ->join("users", "users.id", "=", "hospital_user.user_id")
                    ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date >= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $hospital_user_pending_exclude_one = $hospital_user_pending_exclude_onee->pluck('user_id','user_id')->toArray();

                foreach (array_keys($hospital_user_pending_exclude_one) as $rec) {
                    if (array_key_exists($rec, $hospital_user_exclude_one)) {
                        unset($hospital_user_pending_exclude_one[$rec]);
                    }
                }
    
                $practice_user_pending_count_exclude_onee = DB::table("practice_user")
                    ->select(DB::raw("distinct(concat(practice_user.user_id,'-',hospitals.id)) as user_id"))
                    ->join("users", "users.id", "=", "practice_user.user_id")
                    ->join("practices", "practices.id", "=", "practice_user.practice_id")
                    ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date >= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('users.deleted_at');

                $practice_user_pending_count_exclude_one = $practice_user_pending_count_exclude_onee->pluck('user_id','user_id')->toArray();

                foreach (array_keys($practice_user_pending_count_exclude_one) as $rec) {
                    if (array_key_exists($rec, $practice_user_count_exclude_one)) {
                        unset($practice_user_pending_count_exclude_one[$rec]);
                    }
                }
    
                $hospital_pending_count_exclude_onee = Agreement::select(DB::raw("distinct(agreements.hospital_id) as hospital_id"))
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date >= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    // ->where("agreements.hospital_id", "<>", 113)
                    ->whereRaw("hospitals.archived=0")
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('hospitals.deleted_at');
                    $hospital_pending_count_exclude_one = $hospital_pending_count_exclude_onee->pluck('hospital_id','hospital_id')->toArray();

                foreach (array_keys($hospital_pending_count_exclude_one) as $rec) {
                    if (array_key_exists($rec, $hospital_count_exclude_one)) {
                        unset($hospital_pending_count_exclude_one[$rec]);
                    }
                }
    
                $user_count_exclude_one = count($physician_count_exclude_one) + count($hospital_user_exclude_one) + count($practice_user_count_exclude_one);
                $user_count_distinct_exclude_one = count($physician_count_exclude_one) + count($hospital_user_distinct_exclude_one) + count($practice_user_count_distinct_exclude_one);
                $user_pending_count_exclude_one = count($physician_pending_count_exclude_one) + count($hospital_user_pending_exclude_one) + count($practice_user_pending_count_exclude_one);
                $health_system_user_count_exclude_one = count($health_system_user_exclude_one) + count($health_system_region_user_exclude_one);
    
                $data = [
                    'hospital_count' => Hospital::whereNull('deleted_at')->count(),
                    'practice_count' => Practice::whereNull('deleted_at')->count(),
                    'physician_count' => Physician::whereNull('deleted_at')->count(),
                    'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
                    //'user_count' => User::whereNull('deleted_at')->count(),
                    'user_count' => $user_count_exclude_one,
                    'user_distinct_count' => $user_count_distinct_exclude_one,
                    'multi_facility_users' => $user_count_exclude_one-$user_count_distinct_exclude_one,
                    'health_system_user_count_exclude_one' => $health_system_user_count_exclude_one,
                    'user_pending_count' => $user_pending_count_exclude_one,
                    'physician_count_exclude_one' => count($physician_count_exclude_one),
                    'practice_user_count_exclude_one' => count($practice_user_count_exclude_one),
                    'practice_user_count_distinct_exclude_one' => count($practice_user_count_distinct_exclude_one),
                    'hospital_count_exclude_one' => count($hospital_count_exclude_one),
                    'hospital_user_count_exclude_one' => count($hospital_user_exclude_one),
                    'hospital_user_count_distinct_exclude_one' => count($hospital_user_distinct_exclude_one),
                    'contract_count_exclude_one' => count($contract_count_exclude_one),
                    'hospital_pending_count_exclude_one' => count($hospital_pending_count_exclude_one),
                    'dash' => $dashbord_stats,
                    'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count()
                ];
            } elseif($group_id == 7 || $group_id == 8 ) {
                $default = [0=>'All'];
                if($group_id = 7) {
                    $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                    $regions = $default + HealthSystemRegion::where('health_system_id','=',$system_user->health_system_id)->orderBy('region_name')->pluck('region_name','id')->toArray();
                    $hospital_list =  $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
                }else{
                    $region_user = HealthSystemRegionUsers::where('user_id','=', Auth::user()->id)->first();
                    $regions = HealthSystemRegion::where('id','=',$region_user->health_system_region_id)->pluck('region_name','id')->toArray();
                    $hospital_list =  $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
                }
                $data = [
                    'hospital_count' => Hospital::whereNull('deleted_at')->count(),
                    'practice_count' => Practice::whereNull('deleted_at')->count(),
                    'physician_count' => Physician::whereNull('deleted_at')->count(),
                    'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
                    'user_count' => User::whereNull('deleted_at')->count(),
                    'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),
                    'contract_stats' => Hospital::fetch_contract_stats_for_hospital_users(Auth::user()->id),
                    'note_display_count' => Contract :: fetch_note_display_contracts_count(Auth::user()->id),
                    'regions' =>  $regions,
                    'hospitals' => $hospital_list,
                    'group_id' => Auth::user()->group_id
                ];
            }else {
                $lawson_interfaced_contracts_count_exclude_onee = Contract::select(DB::raw("contracts.id"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("hospitals.archived=0")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->whereNull('contracts.deleted_at')
                    ->where("contracts.is_lawson_interfaced", "=", true)
                    ->where("agreements.hospital_id", "<>", 113);

                    if($group_id == Group::PRACTICE_MANAGER) {
                        $lawson_interfaced_contracts_count_exclude_onee =
                        $lawson_interfaced_contracts_count_exclude_onee->whereIn("agreements.hospital_id", Auth::user()->practices[0]->hospital->pluck('id','id'));
                    }
                    else {
                        $lawson_interfaced_contracts_count_exclude_onee =
                        $lawson_interfaced_contracts_count_exclude_onee->whereIn("agreements.hospital_id", Auth::user()->hospitals->pluck('id','id')->toArray());
                    }
    
                $lawson_interfaced_contracts_count_exclude_one = $lawson_interfaced_contracts_count_exclude_onee->get();
                $data = [
                    'hospital_count' => Hospital::whereNull('deleted_at')->count(),
                    'practice_count' => Practice::whereNull('deleted_at')->count(),
                    'physician_count' => Physician::whereNull('deleted_at')->count(),
                    'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
                    'user_count' => User::whereNull('deleted_at')->count(),
                    'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),
                    'contract_stats' => Hospital::fetch_contract_stats_for_hospital_users(Auth::user()->id),
                    'note_display_count' => Contract :: fetch_note_display_contracts_count(Auth::user()->id),
                    'note_display_count_amended' => Contract :: fetch_note_display_contracts_amended_count(Auth::user()->id),
                    'invoice_dashboard_display'=>Hospital::get_status_invoice_dashboard_display(Auth::user()->id),
                    'lawson_interfaced_contracts_count_exclude_one' => count($lawson_interfaced_contracts_count_exclude_one),
                    'compliance_dashboard_display' => Hospital::get_status_compliance_dashboard_display(),
                    'performance_dashboard_display' => Hospital::get_status_performance_dashboard_display()
                    /*,
                        'agreement_data' => Hospital::agreements_for_hospital_users(Auth::user()->id),
                        'contract_logs_details' => count(Contract :: contract_logs_pending_approval(Auth::user()->id)),
                        'contracts_ready_payment' => Contract :: get_contract_info(Auth::user()->id)*/
                ];
            }

            $user_pie_chart_data[] = [
                "user_id" => $user->id,
                "pie_charts_data" => $data
            ];
        }

        $result = json_encode($user_pie_chart_data);

        Cache::put('pie_charts_data_cached', $result, $cache_seconds);

        return Command::SUCCESS;
    }
}
