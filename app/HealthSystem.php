<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Controllers\Validations\HealthSystemValidation;
use NumberFormatter;
use Request;
use Redirect;
use Lang;
use function App\Start\is_super_user;

class HealthSystem extends Model
{

    use SoftDeletes;

    protected $table = 'health_system';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function createHealthSystem()
    {
        $validation = new HealthSystemValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $healthSystem = new HealthSystem();
        $healthSystem->health_system_name = Request::input('health_system_name');

        if (!$healthSystem->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system.create_error')])
                ->withInput();
        }

        return Redirect::route('healthSystem.index')->with([
            'success' => Lang::get('health_system.create_success')
        ]);
    }

    public static function editSystem($id)
    {
        $system = self::findOrFail($id);

        if (!is_super_user())
            App::abort(403);

        $validation = new HealthSystemValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $system->health_system_name = Request::input('health_system_name');

        if (!$system->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system.edit_error')])
                ->withInput();
        } else {

            return Redirect::route('healthSystem.edit', $system->id)
                ->with(['success' => Lang::get('health_system.edit_success')]);
        }
    }

    public static function getDetails($id)
    {
        $data['system'] = self::findOrFail($id);
        $data['system_users'] = HealthSystemUsers::select('health_system_users.*', 'users.first_name as first_name', 'users.last_name as last_name')
            ->join('users', 'users.id', '=', 'health_system_users.user_id')
            ->whereNull('users.deleted_at')
            ->where('health_system_users.health_system_id', '=', $id)->get();

        $all_physician_count_exclude_onee = DB::table("contracts")
            // ->select(DB::raw("distinct(contracts.physician_id) as physician_id"))    // 6.1.1.12
            // ->join("physicians", "physicians.id", "=", "contracts.physician_id")
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
            ->whereNull('hospitals.deleted_at')
            ->whereNull('physicians.deleted_at');
        $all_physician_count_exclude_one = count($all_physician_count_exclude_onee->get());

        $all_hospital_user_exclude_onee = DB::table("hospital_user")
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
            ->whereNull('hospitals.deleted_at')
            ->whereNull('users.deleted_at');
        $all_hospital_user_exclude_one = count($all_hospital_user_exclude_onee->get());

        $all_practice_user_count_exclude_onee = DB::table("practice_user")
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
            ->whereNull('hospitals.deleted_at')
            ->whereNull('users.deleted_at');
        $all_practice_user_count_exclude_one = count($all_practice_user_count_exclude_onee->get());

        $health_system_user_exclude_onee = DB::table("health_system_users")
            ->select(DB::raw("distinct(concat(health_system_users.user_id,'-',health_system_users.health_system_id)) as user_id"))
            ->join("users", "users.id", "=", "health_system_users.user_id")
            ->join("health_system", "health_system.id", "=", "health_system_users.health_system_id")
            ->whereNull('users.deleted_at')
            ->whereNull('health_system.deleted_at');
        $health_system_user_exclude_one = count($health_system_user_exclude_onee->get());

        $health_system_region_user_exclude_onee = DB::table("health_system_region_users")
            ->select(DB::raw("distinct(concat(health_system_region_users.user_id,'-',health_system.id,'-',health_system_region_users.health_system_region_id)) as user_id"))
            ->join("users", "users.id", "=", "health_system_region_users.user_id")
            ->join("health_system_regions", "health_system_regions.id", "=", "health_system_region_users.health_system_region_id")
            ->join("health_system", "health_system.id", "=", "health_system_regions.health_system_id")
            ->whereNull('users.deleted_at')
            ->whereNull('health_system.deleted_at')
            ->whereNull('health_system_regions.deleted_at');
        $health_system_region_user_exclude_one = count($health_system_region_user_exclude_onee->get());

        $all_user_count_exclude_one = $all_physician_count_exclude_one + $all_hospital_user_exclude_one + $all_practice_user_count_exclude_one + $health_system_user_exclude_one + $health_system_region_user_exclude_one;

        $data['system_regions'] = [];
        $system_regions = HealthSystemRegion::where('health_system_id', '=', $id)->orderBy('region_name')->get();
        $data['system']->user_count = 0;
        $data['system']->pm_count = 0;
        $data['system']->physician_count = 0;
        $data['system']->region_users_count = 0;
        $data['system']->total_users_count = 0;
        $data['system']->active_contracts = 0;
        foreach ($system_regions as $system_region) {
            $region_data['name'] = $system_region->region_name;
            $region_data['region_users'] = HealthSystemRegionUsers::select('health_system_region_users.*', 'users.first_name as first_name', 'users.last_name as last_name')
                ->join('users', 'users.id', '=', 'health_system_region_users.user_id')
                ->whereNull('users.deleted_at')
                ->where('health_system_region_users.health_system_region_id', '=', $system_region->id)
                ->get();
            $region_data['region_hospitals'] = RegionHospitals::select('region_hospitals.*', 'hospitals.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'region_hospitals.hospital_id')
                ->where('region_hospitals.region_id', '=', $system_region->id)
                ->whereNull('hospitals.deleted_at')
                ->get();
            $region_data['user_count'] = 0;
            $region_data['pm_count'] = 0;
            $region_data['active_contracts'] = 0;
            $region_data['physician_count'] = 0;
            $region_data['total_users_count'] = 0;
            foreach ($region_data['region_hospitals'] as $region_hospital) {
                $region_hospital->added_users = 0;
                $physician_count_exclude_onee = DB::table("contracts")
                    // ->select(DB::raw("distinct(contracts.physician_id)"))    // 6.1.1.12
                    // ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->select(DB::raw("distinct(physician_contracts.physician_id) as physician_id"))
                    ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->where("agreements.hospital_id", "=", $region_hospital->hospital_id)
                    ->whereNull('physicians.deleted_at');
                $physician_count_exclude_one = $physician_count_exclude_onee->get();

                $hospital_user_exclude_onee = DB::table("hospital_user")
                    ->select(DB::raw("distinct(hospital_user.user_id)"))
                    ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->join("users", "users.id", "=", "hospital_user.user_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.hospital_id", "=", $region_hospital->hospital_id)
                    ->whereNull('users.deleted_at');
                $hospital_user_exclude_one = $hospital_user_exclude_onee->get();

                $practice_user_count_exclude_onee = DB::table("practice_user")
                    ->select(DB::raw("distinct(practice_user.user_id)"))
                    ->join("practices", "practices.id", "=", "practice_user.practice_id")
                    ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->join("users", "users.id", "=", "practice_user.user_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.hospital_id", "=", $region_hospital->hospital_id)
                    ->whereNull('users.deleted_at');
                $practice_user_count_exclude_one = $practice_user_count_exclude_onee->get();

                $all_active_contracts = DB::table("contracts")
                    // ->select(DB::raw("distinct(contracts.physician_id)"))    // 6.1.1.12
                    // ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->select(DB::raw("distinct(physician_contracts.physician_id) as physician_id"))
                    ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.hospital_id", "=", $region_hospital->hospital_id)
                    ->whereNull('contracts.deleted_at')
                    ->whereNull('physicians.deleted_at')
                    ->get();

                $region_hospital->total_users = count($hospital_user_exclude_one) + count($practice_user_count_exclude_one) + count($physician_count_exclude_one);

                //users, pms, and physicians added last month here
                $last_mo_start_date = date('Y-m-d 00:00:00', strtotime('first day of last month'));
                $last_mo_end_date = date('Y-m-d 23:59:59', strtotime('last day of last month'));
                foreach ($hospital_user_exclude_one as $hospital_user) {
                    $user = User::findOrFail($hospital_user->user_id);
                    if ($user->created_at >= $last_mo_start_date && $user->created_at <= $last_mo_end_date) {
                        $region_hospital->added_users += 1;
                    }
                }
                foreach ($practice_user_count_exclude_one as $practice_user) {
                    $user = User::findOrFail($practice_user->user_id);
                    if ($user->created_at >= $last_mo_start_date && $user->created_at <= $last_mo_end_date) {
                        $region_hospital->added_users += 1;
                    }
                }
                foreach ($physician_count_exclude_one as $physician_user) {
                    $physician = Physician::findOrFail($physician_user->physician_id);
                    if ($physician->created_at >= $last_mo_start_date && $physician->created_at <= $last_mo_end_date) {
                        $region_hospital->added_users += 1;
                    }
                }
                $region_data['hospital_id'] = $region_hospital->id;
                $region_data['user_count'] += count($hospital_user_exclude_one);
                $region_data['pm_count'] += count($practice_user_count_exclude_one);
                $region_data['physician_count'] += count($physician_count_exclude_one);
                $region_data['active_contracts'] += count($all_active_contracts);
            }
            $region_data['total_users_count'] = $region_data['user_count'] + $region_data['pm_count'] + $region_data['physician_count'] + ($region_data['region_users']->count());
            $data['system_regions'][] = $region_data;
            $data['system']->user_count += $region_data['user_count'];
            $data['system']->pm_count += $region_data['pm_count'];
            $data['system']->physician_count += $region_data['physician_count'];
            $data['system']->region_users_count += $region_data['region_users']->count();
            $data['system']->total_users_count += $region_data['total_users_count'];
            $data['system']->active_contracts += $region_data['active_contracts'];
        }
        $data['system']->total_users_count += $data['system_users']->count();
        $pct_formatter = new NumberFormatter('en_US', NumberFormatter::PERCENT);
        $data['system']->percent_of_total_users = $pct_formatter->format($data['system']->total_users_count / $all_user_count_exclude_one);
        return $data;
    }

    public static function deleteSystem($id)
    {
        $system = self::findOrFail($id);
        $system_regions = HealthSystemRegion::where('health_system_id', '=', $id)->orderBy('region_name')->get();
        foreach ($system_regions as $system_region) {
            $delete_region = HealthSystemRegion::deleteRegion($system_region->id);
            if (!$delete_region) {
                return false;
            }
        }

        DB::beginTransaction();

        $system_users = HealthSystemUsers::join('users', 'users.id', '=', 'health_system_users.user_id')
            ->where('health_system_users.health_system_id', '=', $id)->pluck('user_id');

        if (count($system_users) > 0) {
            $remove_users = HealthSystemUsers::where('health_system_id', '=', $id)->delete();
            if (!$remove_users) {
                DB::rollback();
                return false;
            } else {
                $delete_users = User::whereIn('id', $system_users)->delete();
                if (!$delete_users) {
                    DB::rollback();
                    return false;
                }
            }
        }
        if (!$system->delete()) {
            return false;
        }
        DB::commit();
        return true;
    }

    public static function searchSystems($query)
    {
        $systems = self::where('health_system_name', 'like', "%{$query}%")->get();
        return $systems;
    }

    public static function agreements_data_for_health_system_users($user_id, $group_id)
    {
        $contracts_data = array();
        $hospitals_data = array();
        $region_ids = array();
        $hospital_ids = array();
        $agreement_ids = array();
        $region_hospital_ids = array();
        $hospital_agreement_ids = array();
        $region_id = 0;
        $hospital_id = 0;
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name", "health_system_regions.id as region_id", "health_system_regions.region_name as region_name")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id")
                ->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.hospital_id", "<>", 45)
                ->where("health_system_users.user_id", "=", $user_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('health_system_regions.deleted_at')
                ->whereNull('health_system_users.deleted_at')
                ->orderBy("health_system_regions.id")
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();
        } elseif ($group_id == Group::HEALTH_SYSTEM_REGION_USER) {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.hospital_id", "<>", 45)
                ->where("health_system_region_users.user_id", "=", $user_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('health_system_region_users.deleted_at')
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();
        }
        foreach ($allAgreements as $agreement) {
            if ($group_id == Group::HEALTH_SYSTEM_USER) {
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
            } else {
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
        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $region_hospital_ids[$region_id] = $hospital_ids;
        }
        $hospital_agreement_ids[$hospital_id] = $agreement_ids;
        if ($group_id == Group::HEALTH_SYSTEM_USER) {
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
        } else {
            foreach ($hospital_ids as $hospital) {
                if ($group_id == Group::HEALTH_SYSTEM_REGION_USER) {
                    $practice_info = [];
                } else {
                    $practice_info = Contract::get_agreements_contract_info($hospital_agreement_ids[$hospital["hospital_id"]]);
                }
                $contracts_data[] = [
                    'hospital_id' => $hospital["hospital_id"],
                    'hospital_name' => $hospital["hospital_name"],
                    'contracts_info' => $practice_info
                ];
            }
        }
        return $contracts_data;
    }
}
