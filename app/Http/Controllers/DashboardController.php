<?php

namespace App\Http\Controllers;

use App\Hospital;
use App\Practice;
use App\Physician;
use App\PhysicianLog;
use App\User;
use App\Group;
use App\Contract;
use App\HealthSystemUsers;
use App\HealthSystemRegion;
use App\RegionHospitals;
use App\ContractType;
use App\PaymentType;
use App\HealthSystemRegionUsers;
use App\Http\Controllers\Validations\EmailValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\Agreement;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use function App\Start\is_health_system_region_user;
use function App\Start\is_health_system_user;
use function App\Start\is_super_user;

class DashboardController extends BaseController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;
    protected $requireSuperUserOptions = [
        'only' => ['getEmailer', 'postEmailer']
    ];

    public function getIndex()
    {
        $today = date("Y-m-d");
        if ($this->currentUser->password_expiration_date <= $today) {
            return Redirect::route('users.expired', $this->currentUser->id);
        }

        if (is_super_user()) {
            $dashbord_stats = Hospital::fetch_contract_stats_using_union();

            $hospital_count_exclude_onee = DB::table("agreements")
                ->select(DB::raw("distinct(agreements.hospital_id) as hospital_id"))
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.hospital_id", "<>", 113)
                ->whereRaw("hospitals.archived=0")
                ->whereNull('contracts.deleted_at')
                ->whereNull('hospitals.deleted_at');
            $hospital_count_exclude_one = $hospital_count_exclude_onee->pluck('hospital_id', 'hospital_id')->toArray();

            $physician_count_exclude_onee = DB::table("contracts")
                ->select(DB::raw("distinct(physician_contracts.physician_id) as physician_id"))
                ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived=0")
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('physicians.deleted_at');
            $physician_count_exclude_one = $physician_count_exclude_onee->pluck('physician_id', 'physician_id')->toArray();

            $contract_count_exclude_onee = DB::table("contracts")
                ->select(DB::raw("contracts.id"))
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived=0")
                ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->whereNull('contracts.deleted_at')
                ->where("agreements.hospital_id", "<>", 113);
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
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('users.deleted_at');
            $hospital_user_exclude_one = $hospital_user_exclude_onee->pluck('user_id', 'user_id')->toArray();

            $health_system_user_exclude_onee = DB::table("health_system_users")
                ->select(DB::raw("distinct(concat(health_system_users.user_id,'-',health_system_users.health_system_id)) as user_id"))
                ->join("users", "users.id", "=", "health_system_users.user_id")
                ->join("health_system", "health_system.id", "=", "health_system_users.health_system_id")
                ->whereNull('users.deleted_at')
                ->whereNull('health_system.deleted_at');
            $health_system_user_exclude_one = $health_system_user_exclude_onee->pluck('user_id', 'user_id')->toArray();

            $health_system_region_user_exclude_onee = DB::table("health_system_region_users")
                ->select(DB::raw("distinct(concat(health_system_region_users.user_id,'-',health_system.id,'-',health_system_region_users.health_system_region_id)) as user_id"))
                ->join("users", "users.id", "=", "health_system_region_users.user_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "health_system_region_users.health_system_region_id")
                ->join("health_system", "health_system.id", "=", "health_system_regions.health_system_id")
                ->whereNull('users.deleted_at')
                ->whereNull('health_system.deleted_at')
                ->whereNull('health_system_regions.deleted_at');
            $health_system_region_user_exclude_one = $health_system_region_user_exclude_onee->pluck('user_id', 'user_id')->toArray();


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
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('users.deleted_at');
            $practice_user_count_exclude_one = $practice_user_count_exclude_onee->pluck('user_id', 'user_id')->toArray();

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
                ->where("agreements.hospital_id", "<>", 113)
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
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('users.deleted_at');
            $practice_user_count_distinct_exclude_one = $practice_user_count_distinct_exclude_onee->get();

            $physician_pending_count_exclude_onee = DB::table("contracts")
                ->select(DB::raw("distinct(physician_contracts.physician_id) as physician_id"))
                ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date >= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived=0")
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('physicians.deleted_at');
            $physician_pending_count_exclude_one = $physician_pending_count_exclude_onee->pluck('physician_id', 'physician_id')->toArray();
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
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('users.deleted_at');

            $hospital_user_pending_exclude_one = $hospital_user_pending_exclude_onee->pluck('user_id', 'user_id')->toArray();
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
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('contracts.deleted_at')
                ->whereNull('users.deleted_at');
            $practice_user_pending_count_exclude_one = $practice_user_pending_count_exclude_onee->pluck('user_id', 'user_id')->toArray();
            foreach (array_keys($practice_user_pending_count_exclude_one) as $rec) {
                if (array_key_exists($rec, $practice_user_count_exclude_one)) {
                    unset($practice_user_pending_count_exclude_one[$rec]);
                }
            }

            $hospital_pending_count_exclude_onee = DB::table("agreements")
                ->select(DB::raw("distinct(agreements.hospital_id) as hospital_id"))
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date >= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.hospital_id", "<>", 113)
                ->whereRaw("hospitals.archived=0")
                ->whereNull('contracts.deleted_at')
                ->whereNull('hospitals.deleted_at');

            $hospital_pending_count_exclude_one = $hospital_pending_count_exclude_onee->pluck('hospital_id', 'hospital_id')->toArray();

            foreach (array_keys($hospital_pending_count_exclude_one) as $rec) {
                if (array_key_exists($rec, $hospital_count_exclude_one)) {
                    unset($hospital_pending_count_exclude_one[$rec]);
                }
            }
            $user_count_exclude_one = count($physician_count_exclude_one) + count($hospital_user_exclude_one) + count($practice_user_count_exclude_one);
            $user_count_distinct_exclude_one = count($physician_count_exclude_one) + count($hospital_user_distinct_exclude_one) + count($practice_user_count_distinct_exclude_one);
            $user_pending_count_exclude_one = count($physician_pending_count_exclude_one) + count($hospital_user_pending_exclude_one) + count($practice_user_pending_count_exclude_one);
            $health_system_user_count_exclude_one = count($health_system_user_exclude_one) + count($health_system_region_user_exclude_one);

            $practices_page_query = DB::table('practices')->whereNull('practices.deleted_at');
            switch ($this->currentUser->group_id) {
                case Group::HOSPITAL_ADMIN:
                case Group::SUPER_HOSPITAL_USER:
                case Group::HOSPITAL_CFO:
                    $practices_page_query = $practices_page_query
                        ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                        ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                        ->where('hospital_user.user_id', '=', $this->currentUser->id);
                    break;
                case Group::PRACTICE_MANAGER:
                    $practices_page_query = $practices_page_query
                        ->join('practice_user', 'practice_user.practice_id', '=', 'practices.id')
                        ->where('practice_user.user_id', '=', $this->currentUser->id);
                    break;

            }
            $practices_page_count = $practices_page_query->pluck('practices.id')->count();

            $physicians_page_query = DB::table('physicians')
                ->select('physicians.id', 'physicians.email', 'physicians.last_name', 'physicians.first_name', 'physicians.password_text', 'physicians.created_at', 'physician_practices.practice_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join("practices", "physician_practices.practice_id", "=", "practices.id")
                ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                ->where('hospitals.archived', '=', 0)
                ->whereNull("physician_practices.deleted_at")
                ->whereNull("physicians.deleted_at")
                ->distinct();
            $physicians_page_count = $physicians_page_query->cursor()->count();

            $users_page_query = DB::table('users')
                ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                ->whereNull("users.deleted_at")
                ->where('group_id', '=', Group::SUPER_USER)
                ->union(
                    DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('group_id', '=', Group::HOSPITAL_ADMIN)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct()
                )
                ->union(
                    DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('group_id', '=', Group::SUPER_HOSPITAL_USER)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct()
                )
                ->union(
                    DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("practice_user", "users.id", "=", "practice_user.user_id")
                        ->join("practices", "practice_user.practice_id", "=", "practices.id")
                        ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                        ->where('group_id', '=', Group::PRACTICE_MANAGER)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct()
                )
                ->union(
                    DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("health_system_users", "users.id", "=", "health_system_users.user_id")
                        ->where('group_id', '=', Group::HEALTH_SYSTEM_USER)
                        ->distinct()
                )
                ->union(
                    DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("health_system_region_users", "users.id", "=", "health_system_region_users.user_id")
                        ->where('group_id', '=', Group::HEALTH_SYSTEM_REGION_USER)
                        ->distinct()
                )
                ->distinct();
            $users_page_count = $users_page_query->cursor()->count();

            $hospitals_page_query = DB::table('hospitals')->where('hospitals.archived', '=', 0)->whereNull('hospitals.deleted_at');
            $hospitals_page_count = $hospitals_page_query->cursor()->count();
            $new_contracts = DB::table("contracts")
                ->select(DB::raw("distinct(id)"))
                ->whereRaw("date(created_at) >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')")
                ->whereNull('deleted_at')
                ->distinct()
                ->count();
            $data = [
                'hospital_count' => Hospital::whereNull('deleted_at')->count(),
                'practice_count' => Practice::whereNull('deleted_at')->count(),
                'physician_count' => Physician::whereNull('deleted_at')->count(),
                'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
                'user_count' => $user_count_exclude_one,
                'user_distinct_count' => $user_count_distinct_exclude_one,
                'multi_facility_users' => $user_count_exclude_one - $user_count_distinct_exclude_one,
                'health_system_user_count_exclude_one' => $health_system_user_count_exclude_one,
                'new_contracts' => $new_contracts,
                'user_pending_count' => $user_pending_count_exclude_one,
                'physician_count_exclude_one' => count($physician_count_exclude_one),
                'practice_user_count_exclude_one' => count($practice_user_count_exclude_one),
                'practice_user_count_distinct_exclude_one' => count($practice_user_count_distinct_exclude_one),
                'hospital_count_exclude_one' => count($hospital_count_exclude_one),
                'hospital_user_count_exclude_one' => count($hospital_user_exclude_one),
                'hospital_user_count_distinct_exclude_one' => count($hospital_user_distinct_exclude_one),
                'contract_count_exclude_one' => count($contract_count_exclude_one),
                'hospital_pending_count_exclude_one' => count($hospital_pending_count_exclude_one),
                'physicians_page_count' => $physicians_page_count,
                'practices_page_count' => $practices_page_count,
                'users_page_count' => $users_page_count,
                'hospitals_page_count' => $hospitals_page_count,
                'dash' => $dashbord_stats,
                'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count()
            ];
        } elseif (is_health_system_user() || is_health_system_region_user()) {
            $default = [0 => 'All'];
            if (is_health_system_user()) {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
                $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
                $hospitals = RegionHospitals::select('hospitals.id as id')
                    ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                    ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                    ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                    ->get();
            } else {
                $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
                $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id')->toArray();
                $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
                $hospitals = RegionHospitals::select('hospitals.id as id')
                    ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                    ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
                    ->get();
            }
            $start_end_date = Agreement::getHospitalAgreementStartEndDate($hospitals->toArray());
            $data = [
                'hospital_count' => Hospital::whereNull('deleted_at')->count(),
                'practice_count' => Practice::whereNull('deleted_at')->count(),
                'physician_count' => Physician::whereNull('deleted_at')->count(),
                'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
                'user_count' => User::whereNull('deleted_at')->count(),
                'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),
                'contract_stats' => Hospital::fetch_contract_stats_for_hospital_users(Auth::user()->id),
                'note_display_count' => Contract:: fetch_note_display_contracts_count(Auth::user()->id),
                'regions' => $regions,
                'hospitals' => $hospital_list,
                'group_id' => Auth::user()->group_id,
                'agreement_start_period' => $start_end_date['agreement_start_period'],
                'agreement_end_period' => $start_end_date['agreement_end_period']
            ];
        } else {
            $lawson_interfaced_contracts_count_exclude_onee = DB::table("contracts")
                ->select(DB::raw("contracts.id"))
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
            if ($this->currentUser->group_id == Group::PRACTICE_MANAGER) {
                $lawson_interfaced_contracts_count_exclude_onee =
                    $lawson_interfaced_contracts_count_exclude_onee->whereIn("agreements.hospital_id", Auth::user()->practices->first()->hospital->pluck('id', 'id'));
            } else {
                $lawson_interfaced_contracts_count_exclude_onee =
                    $lawson_interfaced_contracts_count_exclude_onee->whereIn("agreements.hospital_id", Auth::user()->hospitals->pluck('id', 'id')->toArray());
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
                'note_display_count' => Contract:: fetch_note_display_contracts_count(Auth::user()->id),
                'note_display_count_amended' => Contract:: fetch_note_display_contracts_amended_count(Auth::user()->id),
                'invoice_dashboard_display' => Hospital::get_status_invoice_dashboard_display(Auth::user()->id),
                'lawson_interfaced_contracts_count_exclude_one' => count($lawson_interfaced_contracts_count_exclude_one),
                'compliance_dashboard_display' => Hospital::get_status_compliance_dashboard_display(),
                'performance_dashboard_display' => Hospital::get_status_performance_dashboard_display()

            ];
        }

        switch ($this->currentUser->group_id) {
            case Group::SUPER_USER:
                return View::make('dashboard/super_user')->with($data);
            case Group::HOSPITAL_ADMIN:
                return View::make('dashboard/hospital_admin_landing_page')->with($data);
            case Group::SUPER_HOSPITAL_USER:
                return View::make('dashboard/hospital_admin_landing_page')->with($data);
            case Group::PRACTICE_MANAGER:
                $user = User::where('email', '=', Auth::user()->email)->first();
                if ($user) {
                    return Redirect::route('practicemanager.dashboard', $user->id);
                } else {
                    return View::make('auth/login');
                }
            case Group::HOSPITAL_CFO:
                return View::make('dashboard/hospital_admin_landing_page')->with($data);
            case Group::Physicians:
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id);
                } else {
                    return View::make('auth/login');
                }
            case Group::HEALTH_SYSTEM_USER:
                return View::make('dashboard/health_system_user_landing_page')->with($data);
            case Group::HEALTH_SYSTEM_REGION_USER:
                return View::make('dashboard/health_system_user_landing_page')->with($data);
        }
    }

    public function getHospitalAgreementStartEndDate($region_id, $facility)
    {
        if (Request::ajax()) {
            if ($facility == 0) {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $hospitals = RegionHospitals::select('hospitals.id as id')
                    ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                    ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                    ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                    ->get();

                $data = Agreement::getHospitalAgreementStartEndDate($hospitals->toArray());
            } else {
                $hospitals[] = $facility;
                $data = Agreement::getHospitalAgreementStartEndDate($hospitals);
            }

            return $data;
        }
    }

    public function getEmailer()
    {
        return View::make('dashboard/emailer');
    }

    public function postEmailer()
    {
        ini_set('max_execution_time', 6000);
        $validation = new EmailValidation();
        if (!$validation->validateEmail(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $emails = [];

        if (Request::has('super_users') || Request::has('hospital_admins')
            || Request::has('practice_managers') || Request::has('super_hospital_users')) {
            $list = DB::table('users')->where(function ($query) {
                if (Request::has('super_users')) $query->where('group_id', '=', Group::SUPER_USER);
                if (Request::has('hospital_admins')) $query->where('group_id', '=', Group::HOSPITAL_ADMIN);
                if (Request::has('practice_managers')) $query->where('group_id', '=', Group::PRACTICE_MANAGER);
                if (Request::has('super_hospital_users')) $query->where('group_id', '=', Group::SUPER_HOSPITAL_USER);
            })->pluck('email')->toArray();

            $emails = array_merge($emails, $list);
        }

        if (Request::has('physicians')) {
            $list = DB::table('physicians')->pluck('email')->toArray();
            $emails = array_merge($emails, $list);
        }

        $email_count = count($emails);
        $subject = Request::input('subject');
        $body = Request::input('body');

        if ($email_count == 0) {
            return Redirect::back()->with(['error' => Lang::get('emailer.error')]);
        }

        foreach ($emails as $email) {
            $data = [
                'name' => '',
                'email' => $email,
                'type' => EmailSetup::MASS_EMAILER,
                'with' => [
                    'body' => $body
                ],
                'subject_param' => [
                    'name' => $subject,
                    'date' => '',
                    'month' => '',
                    'year' => '',
                    'requested_by' => '',
                    'manager' => '',
                    'subjects' => ''
                ]
            ];

            EmailQueueService::sendEmail($data);
        }

        return Redirect::back()->with([
            'success' => Lang::get('emailer.success', ['count' => count($emails)])
        ]);
    }

    /*
    @description:fetch contract log count asynchronously
    @return - json
    */

    public function getDashboardForAdmin()
    {
        $data = [
            'hospital_count' => Hospital::count(),
            'practice_count' => Practice::count(),
            'physician_count' => Physician::count(),
            'log_count' => PhysicianLog::count(),
            'user_count' => User::count(),
            'online_count' => User::whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),
            'contract_stats' => Hospital::fetch_contract_stats_for_hospital_users(Auth::user()->id),
            'note_display_count' => Contract:: fetch_note_display_contracts_count(Auth::user()->id),
            'agreement_data' => Hospital::agreements_for_hospital_users(Auth::user()->id),
            'contract_logs_details' => count(Contract:: contract_logs_pending_approval(Auth::user()->id)),
            'contracts_ready_payment' => Contract:: get_contract_info(Auth::user()->id)
        ];


        return View::make('dashboard/hospital_admin')->with($data);
    }

    /*
    @description:fetch user agreement details asynchronously
    @return - json
    */

    public function getContractDetailsCount()
    {
        if (Request::ajax()) {
            /*This change is done to load contract log details to be loaded asynchronously*/
            $data['contract_logs_details'] = count(Contract:: contract_logs_pending_approval(Auth::user()->id));
            return Response::json($data);
        }
    }

    /*
    @description:fetch contract payment count asynchronously
    @return - json
    */

    public function getAgreementDataByAjax()
    {
        if (Request::ajax()) {
            $data['agreement_data'] = Hospital::agreements_for_hospital_users(Auth::user()->id);
            if (is_health_system_user()) {
                return View::make('dashboard/region_data_ajax')->with($data);
            } else {
                return View::make('dashboard/agreement_data_ajax')->with($data);
            }
        }
    }

    /*
    @description:fetch Facilites for regions and system
    @return - json
    */

    public function getContractPaymentCounts()
    {
        if (Request::ajax()) {
            $data['contracts_ready_payment'] = Contract:: get_contract_info(Auth::user()->id);
            return Response::json($data);
        }
    }

    public function getRegionFacilities($region_id)
    {
        if (Request::ajax()) {
            $default = [0 => 'All'];
            $sorted_array = array();
            $options = array();
            $hospitals = [];
            $data = [];
            if ($region_id != 0) {
                $hospital_list = RegionHospitals::getAllRegionHospitals($region_id);
            } else {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $hospital_list = RegionHospitals::getAllSystemHospitals($system_user->health_system_id);
            }
            foreach ($hospital_list as $id => $hospital) {
                $options[] = ['name' => $hospital, 'id' => $id];
                $hospitals [] = $id;
            }

            $start_end_date = Agreement::getHospitalAgreementStartEndDate($hospitals);

            asort($options);
            $sorted_array = array_values($options);
            $data = [
                "sorted_array" => $sorted_array,
                "agreement_start_period" => $start_end_date['agreement_start_period'],
                "agreement_end_period" => $start_end_date['agreement_end_period']
            ];
            return $data;
        }
    }

    /*
    @description:fetch user Active contract types chart asynchronously
    @return - json
    */

    public function getActiveContractTypesChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['type_data'] = ContractType::fetch_contract_stats_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date, 1);
            return View::make('dashboard/active_contract_types_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts
    @return - json
    */
    public function getActiveContractsByType($region, $facility, $typeId, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $contracts = ContractType::getContractsByTypeForRegionAndHealthSystem($typeId, Auth::user()->id, $group, $region, $facility, false, $start_date, $end_date);
            $data = array();
            foreach ($contracts as $contract) {
                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => format_date($contract->agreement_start_date),
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
                ];
            }
            return $data;
        }
    }

    /*
    @description:fetch user Contract Spend YTD chart
    @return - json
    */
    public function getContractSpendYTDChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {

            $data['spend_data'] = ContractType::fetch_contract_spendYTD_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return View::make('dashboard/contract_spend_YTD_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts spend YTD
    @return - json
    */
    public function getContractSpendYTD($region, $facility, $typeId, $total, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = ContractType::getContractSpendYTD($typeId, Auth::user()->id, $group, $region, $facility, $total, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch user Contract Effectiveness chart
    @return - json
    */
    public function getContractTypesEffectivenessChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['effectivness_data'] = ContractType::fetch_contract_effectiveness_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return View::make('dashboard/contract_effectivness_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts type Effectiveness
    @return - json
    */
    public function getActiveContractsTypeEffectivness($region, $facility, $typeId, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = ContractType::getContractsTypeEffectivness($typeId, Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch user Contract spend Effectiveness chart
    @return - json
    */
    public function getContractSpendEffectivenessChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['effectivness_data'] = PaymentType::fetch_contract_effectiveness_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return View::make('dashboard/contract_spend_effectivness_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts spend Effectiveness
    @return - json
    */
    public function getActiveContractSpendEffectivness($region, $facility, $typeId, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = PaymentType::getContractSpendEffectivness($typeId, Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch user Contract spend to actual chart
    @return - json
    */
    public function getContractSpendToActualChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['spend_to_actual_data'] = ContractType::fetch_contract_spend_to_actual_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return View::make('dashboard/contract_spend_to_actual_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts spend Effectiveness
    @return - json
    */
    public function getContractSpendToActual($region, $facility, $typeId, $totalSpend, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = ContractType::getContractSpendToActual($typeId, Auth::user()->id, $group, $region, $facility, $totalSpend, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch user Contract alerts chart
    @return - json
    */
    public function getContractTypesAlertsChart($region, $facility, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['alerts_data'] = ContractType::fetch_contract_type_alerts_for_health_system_users(Auth::user()->id, $group, $region, $facility, $start_date, $end_date);
            return View::make('dashboard/contract_type_alerts_chart')->with($data);
        }
    }

    /*
    @description:fetch user Active contracts alert
    @return - json
    */
    public function getContractTypesAlerts($region, $facility, $typeId, $payment, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = ContractType::getContractAlerts($typeId, Auth::user()->id, $group, $region, $facility, $payment, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch user Active contract types chart by region asynchronously
    @return - json
    */
    public function getContractTypesByRegionChart($group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data['regions_data'] = ContractType::getContractTypesByRegionChart(Auth::user()->id, $group, $start_date, $end_date);
            return View::make('dashboard/active_contract_types_by_region_chart')->with($data);
        }
    }

    /*
    @description:fetch facility Active contract types counts
    @return - json
    */
    public function getFacilityContractCountDataByAjax($region_id, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $data = ContractType::getContractCountsByFacility(Auth::user()->id, $region_id, $group, $start_date, $end_date);
            return $data;
        }
    }

    /*
    @description:fetch facility Active contract list
    @return - json
    */
    public function getFacilityActiveContracts($hospital_name, $group)
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        if (Request::ajax()) {
            $hospital = Hospital::where('name', '=', trim($hospital_name))->first();
            $contracts = Contract::getContractsForRegionAndHealthSystemUsers(0, 0, Auth::user()->id, $group, 0, $hospital->id, false, $start_date, $end_date);
            $sorteddata = array();
            $data = array();
            foreach ($contracts as $contract) {
                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => format_date($contract->agreement_start_date),
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
                ];
            }

            asort($data);
            $sorteddata = array_values($data);
            return $sorteddata;
        }
    }

    /*
    @description:fetch facility Active contract pluck
    @return - json
    */
    public function getFacilityContractSpecifyDataByAjax($hospital_id)
    {
        if (Request::ajax()) {
            $data['contracts_info'] = Contract::get_hospitals_contract_info($hospital_id);
            return View::make('dashboard/_hospital_data_ajax')->with($data);
        }
    }

    /*function to display healthsytem dashboard*/
    function showHealthSystemDashboard()
    {
        $default = [0 => 'All'];

        $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
        $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
        $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
        $group_id = 7;

        $hospitals = RegionHospitals::select('hospitals.id as id')
            ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
            ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
            ->get();

        $start_end_date = Agreement::getHospitalAgreementStartEndDate($hospitals->toArray());

        $data = [
            'hospital_count' => Hospital::whereNull('deleted_at')->count(),
            'practice_count' => Practice::whereNull('deleted_at')->count(),
            'physician_count' => Physician::whereNull('deleted_at')->count(),
            'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
            'user_count' => User::whereNull('deleted_at')->count(),
            'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),
            'regions' => $regions,
            'hospitals' => $hospital_list,
            'group_id' => $group_id,
            'agreement_start_period' => $start_end_date['agreement_start_period'],
            'agreement_end_period' => $start_end_date['agreement_end_period']
        ];

        return View::make('dashboard/display_health_system_landing_page')->with($data);
    }

    function showHealthSystemRegionDashboard()
    {
        $default = [0 => 'All'];

        $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
        $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id');
        $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
        $group_id = 8;

        $hospitals = RegionHospitals::select('hospitals.id as id')
            ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
            ->orderBy('hospitals.name')
            ->get();

        $start_end_date = Agreement::getHospitalAgreementStartEndDate($hospitals->toArray());

        $data = [
            'hospital_count' => Hospital::whereNull('deleted_at')->count(),
            'practice_count' => Practice::whereNull('deleted_at')->count(),
            'physician_count' => Physician::whereNull('deleted_at')->count(),
            'log_count' => PhysicianLog::whereNull('deleted_at')->count(),
            'user_count' => User::whereNull('deleted_at')->count(),
            'online_count' => User::whereNull('deleted_at')->whereRaw('seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')->count(),

            'regions' => $regions,
            'hospitals' => $hospital_list,
            'group_id' => $group_id,
            'agreement_start_period' => $start_end_date['agreement_start_period'],
            'agreement_end_period' => $start_end_date['agreement_end_period']
        ];
        return View::make('dashboard/display_health_system_region_landing_page')->with($data);
    }

    /*function to display compliance dashboard*/
    function showComplianceDashboard()
    {
        $default = [0 => 'All'];
        $user_id = Auth::user()->id;
        $hospitals = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospital_list = array();
        foreach ($hospitals as $hospital_id => $hospital_name) {
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospital_list = $hospital;
            }
        }

        $hospital_list = $default + $hospital_list;

        $group_id = Group::select("groups.id")
            ->join('users', 'users.group_id', '=', 'groups.id')
            ->where('users.id', '=', $user_id)
            ->first();

        $data = [
            'hospitals' => $hospital_list,
            'group_id' => $group_id->id
        ];
        return View::make('dashboard/display_compliance_system_landing_page')->with($data);
    }

    /*
    @description:fetch rejection logs
    @return - json
    */
    public function getRejectionRateChart($facility)
    {
        if (Request::ajax()) {
            $data['log_rejection_data'] = PhysicianLog::fetch_log_rejection_rate(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_rejection_rate_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection logs - overall compared
    @return - json
    */
    public function getRejectionRateOverallcomparedChart($facility)
    {
        if (Request::ajax()) {
            $data['log_rejection_data'] = PhysicianLog::fetch_log_rejection_overall_rate(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_rejection_rate_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection by physician chart
    @return - json
    */
    public function getRejectionByphysicianChart($facility)
    {
        $popupdata = 0;
        $physician_id = 0;

        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_rejection_by_physician_rate(Auth::user()->id, $facility, $physician_id, $popupdata);
            return View::make('dashboard/compliance_rejection_by_physician_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection by contract types chart
    @return - json
    */
    public function getRejectionByContractTypeChart($facility)
    {
        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_rejection_by_contract_type_rate(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_rejection_by_contract_type_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection by practice chart
    @return - json
    */
    public function getRejectionByPracticeChart($facility)
    {
        if (Request::ajax()) {
            $popupdata = 0;
            $practice_id = 0;
            $data['type_data'] = PhysicianLog::fetch_rejection_by_practice_rate(Auth::user()->id, $facility, $practice_id, $popupdata);
            return View::make('dashboard/compliance_rejection_by_practice_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection by reason chart
    @return - json
    */
    public function getRejectionByReasonChart($facility)
    {
        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_rejection_by_reason_rate(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_rejection_by_reason_chart')->with($data);
        }
    }

    /*
    @description:fetch rejection by approver chart
    @return - json
    */
    public function getRejectionByApproverChart($facility)
    {
        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_rejection_by_approver_rate(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_rejection_by_approver_chart')->with($data);
        }
    }

    /*
    @description:fetch Average duration of approval time chart
    @return - json
    */
    public function getAverageDurationOfApprovalTimeChart($facility)
    {
        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_average_duration_of_approval_time(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_average_duration_of_approval_time_chart')->with($data);
        }
    }

    /*
    @description:fetch Average duration of time between approve logs  chart
    @return - json
    */
    public function getAverageDurationOfTimeBetweenApproveLogs($facility)
    {
        if (Request::ajax()) {
            $data['type_data'] = PhysicianLog::fetch_average_duration_of_time_between_approve_logs(Auth::user()->id, $facility);
            return View::make('dashboard/compliance_average_duration_of_time_between_approve_logs_chart')->with($data);
        }
    }

    /*
       @description:fetch Rejection by practice
       @return - json
       */

    public function getComplianceRejectionByPractice($facility, $practice_id)
    {

        if (Request::ajax()) {
            $popupdata = 1;
            $data = PhysicianLog::fetch_rejection_by_practice_rate(Auth::user()->id, $facility, $practice_id, $popupdata);
            return $data;
        }
    }

    /*
    @description:fetch Rejection by practice
    @return - json
    */

    public function getComplianceRejectionByPhysician($facility, $physician_id)
    {

        if (Request::ajax()) {
            $data = PhysicianLog::fetch_compliance_rejectionby_physician(Auth::user()->id, $facility, $physician_id);
            return $data;
        }
    }

    /*
    @description:get Average duration of Payment Approval data PopUp
    @return - json
    */
    public function getAverageDurationOfPaymentApprovalPopUp($facility, $contract_type_id)
    {
        if (Request::ajax()) {
            $data = PhysicianLog::fetch_average_duration_of_approval_time(Auth::user()->id, $facility, $contract_type_id);
            return $data;
        }
    }

    /*
    @description:get Average duration of Provider Approval data PopUp
    @return - json
    */
    public function getAverageDurationOfProviderApprovalPopUp($facility, $contract_type_id)
    {
        if (Request::ajax()) {
            $data = PhysicianLog::fetch_average_duration_of_time_between_approve_logs(Auth::user()->id, $facility, $contract_type_id);
            return $data;
        }
    }

    /*
    @description:get compliance rection rate by overall data PopUp
    @return - json
    */

    public function getComplianceRejectionRateOverall($facility, $organization_id)
    {
        if (Request::ajax()) {
            $data = PhysicianLog::fetch_compliance_rejection_overall_rate(Auth::user()->id, $facility, $organization_id);
            return $data;
        }
    }

    public function postHospitalsContractTotalSpendAndPaid()
    {
        $data = Hospital::postHospitalsContractTotalSpendAndPaid($hospital_id = 0);
        return $data;
    }

    public function updateTotalAndRejectedLogsForHospital()
    {
        $data = PhysicianLog::updateTotalAndRejectedLogs($hospital_id = 0);
        return $data;
    }
}
