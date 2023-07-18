<?php
namespace App\Http\Controllers;

use App\HealthSystem;
use App\Group;
use App\HealthSystemUsers;
use App\HealthSystemRegion;
use App\RegionHospitals;
use App\HealthSystemReport;
use App\PaymentType;
use App\ContractType;
use App\HealthSystemRegionUsers;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Agreement;
use App\Physician;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_user;
use function App\Start\health_system_report_path;

class HealthSystemController extends ResourceController
{
    protected $requireAuth = true;

    public function getIndex()
    {
        if (!is_super_user())
            App::abort(403);

        $options = [
            'filter' => Request::input('filter', 1),
            'sort' => Request::input('sort', 1),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 1,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['health_system_name'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        $data = $this->query('HealthSystem', $options, function ($query, $options) {
            return $query->whereNull('deleted_at');
        });


        $value = Cache::get('facility_info_cached');

        if (!is_null($value)) {
            $data['facility_count'] = json_decode($value, true);
        }

        $data['table'] = View::make('health_system/partials/table')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('health_system/create');
    }

    public function postCreate()
    {
        $result = HealthSystem::createHealthSystem();
        return $result;
    }

    public function getShow($id)
    {
        $data = HealthSystem::getDetails($id);

        return View::make('health_system/show')->with($data);
    }

    public function getEdit($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (!is_super_user())
            App::abort(403);


        $data = [
            "system" => $system
        ];

        return View::make('health_system/edit')->with($data);
    }

    public function postEdit($id)
    {
        if (!is_super_user())
            App::abort(403);


        $result = HealthSystem::editSystem($id);
        return $result;
    }

    public function getUsers($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (!is_super_user())
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($system) {
            return $query->select('users.*')
                ->join('health_system_users', 'health_system_users.user_id', '=', 'users.id')
                ->where('health_system_users.health_system_id', '=', $system->id)
                ->whereNull('health_system_users.deleted_at');
        });

        $data['system'] = $system;
        $data['table'] = View::make('health_system/_users')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/users')->with($data);
    }

    public function getCreateUser($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (is_super_user()) {
            $groups = [
                '7' => Group::findOrFail(7)->name
            ];

            return View::make('health_system/create_user')->with([
                'system' => $system,
                'groups' => $groups
            ]);
        } else {
            App::abort(403);
        }
    }

    public function postCreateUser($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (is_super_user()) {
            $result = HealthSystemUsers::createSystemUser($system);
            return $result;
        } else {
            App::abort(403);
        }
    }

    //function to load view of add existing hospital user as a healthsystem user
    public function getAddUser($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (is_super_user()) {
            $groups = [
                '7' => Group::findOrFail(7)->name
            ];

            return View::make('health_system/add_user')->with([
                'system' => $system,
                'groups' => $groups
            ]);
        } else {
            App::abort(403);
        }
    }

    //function to save existing hospital user as a healthsystem user
    public function postAddUser($id)
    {
        //$system = HealthSystem::findOrFail($id);
        $email = Request::input('email');
        $group = Request::input('group');
        $region = 0;

        if (is_super_user()) {
            $result = HealthSystemUsers::addSystemUser($id, $email, $group);
            if ($result['response'] == 'error') {
                return Redirect::back()
                    ->with(['error' => $result['msg']]);
            } else {
                return Redirect::route('healthSystem.users', $id)
                    ->with(['success' => $result['msg']]);
            }

        } else {
            App::abort(403);
        }
    }


    public function getRegions($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (!is_super_user())
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 1),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 1,
            'appends' => ['sort', 'order'],
            'field_names' => ['region_name']
        ];

        $data = $this->query('HealthSystemRegion', $options, function ($query, $options) use ($system) {
            return $query->select('health_system_regions.*')
                ->join('health_system', 'health_system.id', '=', 'health_system_regions.health_system_id')
                ->where('health_system.id', '=', $system->id)
                ->whereNull('health_system.deleted_at');
        });

        $data['system'] = $system;
        $data['table'] = View::make('health_system/_regions')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/regions')->with($data);
    }

    public function getCreateRegion($id)
    {
        if (!is_super_user())
            App::abort(403);

        $data['system'] = HealthSystem::findOrFail($id);
        return View::make('health_system_region/create')->with($data);
    }

    public function postCreateRegion($id)
    {
        $system = HealthSystem::findOrFail($id);

        if (is_super_user()) {

            $result = HealthSystemRegion::createSystemRegion($system);
            return $result;
        } else {
            App::abort(403);
        }
    }

    public function getDelete($id)
    {
        if (!is_super_user())
            App::abort(403);

        $result = HealthSystem::deleteSystem($id);

        if (!$result) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system.delete_error')])
                ->withInput();
        } else {
            return Redirect::route('healthSystem.index')
                ->with(['success' => Lang::get('health_system.delete_success')]);
        }
    }

    public function getReports($group)
    {
        //if(!is_health_system_user() && !is_health_system_region_user())
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);


        $default = [0 => 'All'];
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
            $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
            $system = HealthSystem::findOrFail($system_user->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                ->get();
        } else {
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $region = HealthSystemRegion::findOrFail($region_user->health_system_region_id);
            $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id');
            $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
            $system = HealthSystem::findOrFail($region->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
                ->get();
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system) {
                return $query->where('health_system_id', '=', $system->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::ACTIVE_CONTRACTS_REPORTS);
            });
        } else {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system, $region) {
                return $query->where('health_system_id', '=', $system->id)
                    ->where("health_system_region_id", "=", $region->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::ACTIVE_CONTRACTS_REPORTS);
            });
        }
        $data['group'] = $group;
        $data['table'] = View::make('health_system/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['system'] = $system;
        $data['regions'] = $regions;
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data["region_id"] = Request::input("region", 0);
        } else {
            $data['region'] = $region;
            $data["region_id"] = $region->id;
        }
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);

        if ($data["facility"] == 0) {
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospitals->toArray());
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospitals->toArray(), $agreements, $start_date, $end_date);
        } else {
            $hospital[] = $data["facility"];
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospital);
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospital, $agreements, $start_date, $end_date);
        }

        if ($agreements != null) {
            foreach ($agreements as $agreement) {
                if (isset($start_date[$agreement]) != null && isset($end_date[$agreement]) != null) {
                    $start = $start_date[$agreement];
                    $end = $end_date[$agreement];
                    $data['selected_start_date'][$agreement] = $start;
                    $data['selected_end_date'][$agreement] = $end;
                } else {
                    $data['selected_start_date'][$agreement] = isset($start_date[$agreement]);
                    $data['selected_end_date'][$agreement] = isset($end_date[$agreement]);
                }
            }
        }

        $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        $data['form'] = View::make('layouts/_health_system_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return Response::json($data);
        }
        return View::make('health_system/reports')->with($data);
    }

    public function postActiveContractsReports($group)
    {
        //if(!is_health_system_user() && !is_health_system_region_user())
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);
        $result = HealthSystemReport::getReportData($group);
        return $result;
    }

    public function getContractExpiringReports($group)
    {
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);


        $default = [0 => 'All'];
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
            $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
            $system = HealthSystem::findOrFail($system_user->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                ->get();
        } else {
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $region = HealthSystemRegion::findOrFail($region_user->health_system_region_id);
            $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id');
            $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
            $system = HealthSystem::findOrFail($region->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
                ->get();
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system) {
                return $query->where('health_system_id', '=', $system->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::CONTRACTS_EXPIRING_REPORTS);
            });
        } else {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system, $region) {
                return $query->where('health_system_id', '=', $system->id)
                    ->where("health_system_region_id", "=", $region->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::CONTRACTS_EXPIRING_REPORTS);
            });
        }
        $data['group'] = $group;
        $data['table'] = View::make('health_system/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['system'] = $system;
        $data['regions'] = $regions;
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data["region_id"] = Request::input("region", 0);
        } else {
            $data['region'] = $region;
            $data["region_id"] = $region->id;
        }
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);

        if ($data["facility"] == 0) {
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospitals->toArray());
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospitals->toArray(), $agreements, $start_date, $end_date);
        } else {
            $hospital[] = $data["facility"];
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospital);
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospital, $agreements, $start_date, $end_date);
        }

        if ($agreements != null) {
            foreach ($agreements as $agreement) {
                if (isset($start_date[$agreement]) != null && isset($end_date[$agreement]) != null) {
                    $start = $start_date[$agreement];
                    $end = $end_date[$agreement];
                    $data['selected_start_date'][$agreement] = $start;
                    $data['selected_end_date'][$agreement] = $end;
                } else {
                    $data['selected_start_date'][$agreement] = isset($start_date[$agreement]);
                    $data['selected_end_date'][$agreement] = isset($end_date[$agreement]);
                }
            }
        }

        $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        $data['form'] = View::make('layouts/_health_system_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/contract_expiring_reports')->with($data);
    }

    public function postContractsExpiringReport($group)
    {
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);
        $result = HealthSystemReport::getContractExpiringReportData($group);
        return $result;
    }

    public function getReport($report_id, $group)
    {
        $report = HealthSystemReport::findOrFail($report_id);

        //if (!is_health_system_user() && !is_health_system_region_user())
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);

        $filename = health_system_report_path($report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteReport($report_id, $group)
    {
        $report = HealthSystemReport::findOrFail($report_id);

        //if (!is_health_system_user() && !is_health_system_region_user())
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('health_system.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('health_system.delete_report_success')
        ]);
    }

    //Report data fetching for getspendYTDEffectivenessReports
    public function getspendYTDEffectivenessReports($group_id)
    {
        //if(!is_health_system_user() && !is_health_system_region_user())
        if (!($group_id == Group::HEALTH_SYSTEM_USER) && !($group_id == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);
        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;


        $default = [0 => 'All'];
        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();

            $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
            $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
            $payment_type_list = $default + PaymentType::getAllSystemPaymentTypes($user_id, $group_id, 0, 0, 0);
            $contract_type_list = $default + ContractType::getAllSystemContractTypes($user_id, $group_id, 0, 0, 0);
            $system = HealthSystem::findOrFail($system_user->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                ->get();
        } else {
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $region = HealthSystemRegion::findOrFail($region_user->health_system_region_id);
            $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id');
            $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
            $payment_type_list = $default + PaymentType::getAllSystemPaymentTypes($user_id, $group_id, 0, 0, 0);
            $contract_type_list = $default + ContractType::getAllSystemContractTypes($user_id, $group_id, $region_user->health_system_region_id, 0, 0);
            $system = HealthSystem::findOrFail($region->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
                ->get();
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system) {
                return $query->where('health_system_id', '=', $system->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::SPEND_YTD_EFFECTIVENESS_REPORTS);
            });
        } else {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system, $region) {
                return $query->where('health_system_id', '=', $system->id)
                    ->where("health_system_region_id", "=", $region->id)
                    //->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::SPEND_YTD_EFFECTIVENESS_REPORTS);
            });
        }
        $data['group'] = $group_id;
        $data['table'] = View::make('health_system/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['system'] = $system;
        $data['regions'] = $regions;
        if (($group_id == Group::HEALTH_SYSTEM_USER)) {
            $data["region_id"] = Request::input("region", 0);
        } else {
            $data['region'] = $region;
            $data["region_id"] = $region->id;
        }
        $data['facilities'] = $hospital_list;
        $data['contract_types'] = $contract_type_list;
        $data['payment_types'] = $payment_type_list;
        $data["facility"] = Request::input("facility", 0);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);

        if ($data["facility"] == 0) {
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospitals->toArray());
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospitals->toArray(), $agreements, $start_date, $end_date);
        } else {
            $hospital[] = $data["facility"];
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospital);
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospital, $agreements, $start_date, $end_date);
        }

        if ($agreements != null) {
            foreach ($agreements as $agreement) {
                if (isset($start_date[$agreement]) != null && isset($end_date[$agreement]) != null) {
                    $start = $start_date[$agreement];
                    $end = $end_date[$agreement];
                    $data['selected_start_date'][$agreement] = $start;
                    $data['selected_end_date'][$agreement] = $end;
                } else {
                    $data['selected_start_date'][$agreement] = isset($start_date[$agreement]);
                    $data['selected_end_date'][$agreement] = isset($end_date[$agreement]);
                }
            }
        }

        $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        $data['form'] = View::make('layouts/_health_system_reports_spendYTDEffectiveness_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/spendYTDandEffectiveness')->with($data);
    }

    public function postspendYTDEffectivenessReports($group_id)
    {
        //if(!is_health_system_user() && !is_health_system_region_user())
        if (!($group_id == Group::HEALTH_SYSTEM_USER) && !($group_id == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);
        $result = HealthSystemReport::getContractSpendYTDEffectivenessReportData($group_id);
        return $result;
    }

    /*
    @description:fetch Facilities, contract type & payment type as per selected region for regions and system
    @return - json
    */
    public function getRegionFacilitiesCTypePTypeData($region_id)
    {
        if (Request::ajax()) {
            $default = [0 => 'All'];
            $user_id = Auth::user()->id;
            $group_id = Auth::user()->group_id;
            $hospital_options = array();
            $response = array();
            if ($region_id != 0) {
                $hospital_list = RegionHospitals::getAllRegionHospitals($region_id);
            } else {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $hospital_list = RegionHospitals::getAllSystemHospitals($system_user->health_system_id);
            }

            $payment_type_options = array();
            $contract_type_options = array();
            foreach ($hospital_list as $id => $hospital) {
                $hospital_options[] = ['name' => $hospital, 'id' => $id];
                //find payment type data from each hospital
                $payment_type_data = PaymentType::getAllSystemPaymentTypes($user_id, $group_id, $region_id, $id, 0);
                foreach ($payment_type_data as $key => $value) {
                    if (!(array_key_exists($key, $payment_type_options))) {
                        $payment_type_options[$key] = ['name' => $value, 'id' => $key];
                    }
                }
                //find contract type data from each hospital
                $contract_type_data = ContractType::getAllSystemContractTypes($user_id, $group_id, $region_id, $id, 0);
                foreach ($contract_type_data as $key => $value) {
                    if (!(array_key_exists($key, $contract_type_options))) {
                        $contract_type_options[$key] = ['name' => $value, 'id' => $key];
                    }
                }
            }
            asort($hospital_options);
            asort($payment_type_options);
            asort($contract_type_options);
            $response['facility'] = array_values($hospital_options);
            $response['payment_types'] = array_values($payment_type_options);
            $response['contract_types'] = array_values($contract_type_options);
            return $response;
        }
    }

    /*
    @description:fetch contract type & payment type as per selected region & hospital for regions and system,
    @return - json
    */
    public function getFacilitiesCTypePTypeData($region_id, $hospital_id)
    {
        if (Request::ajax()) {
            $default = [0 => 'All'];
            $user_id = Auth::user()->id;
            $group_id = Auth::user()->group_id;
            $hospital_options = array();
            $response = array();
            if ($region_id != 0) {
                $hospital_list = RegionHospitals::getAllRegionHospitals($region_id);
            } else {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $hospital_list = RegionHospitals::getAllSystemHospitals($system_user->health_system_id);
            }

            $payment_type_options = array();
            $contract_type_options = array();

            //find payment type data from hospital
            $payment_type_data = PaymentType::getAllSystemPaymentTypes($user_id, $group_id, $region_id, $hospital_id, 0);
            foreach ($payment_type_data as $key => $value) {
                if (!(array_key_exists($key, $payment_type_options))) {
                    $payment_type_options[$key] = ['name' => $value, 'id' => $key];
                }
            }
            //find contract type data from hospital
            $contract_type_data = ContractType::getAllSystemContractTypes($user_id, $group_id, $region_id, $hospital_id, 0);
            foreach ($contract_type_data as $key => $value) {
                if (!(array_key_exists($key, $contract_type_options))) {
                    $contract_type_options[$key] = ['name' => $value, 'id' => $key];
                }
            }

            asort($payment_type_options);
            asort($contract_type_options);

            $response['payment_types'] = array_values($payment_type_options);
            $response['contract_types'] = array_values($contract_type_options);
            return $response;
        }
    }

    /*
    @description:fetch contract type as per selected payment type,hospital & region for regions and system
    @return - json
    */
    public function getPTypeCTypeData($region_id, $hospital_id, $payment_type_id)
    {
        if (Request::ajax()) {
            $default = [0 => 'All'];
            $user_id = Auth::user()->id;
            $group_id = Auth::user()->group_id;
            $hospital_options = array();
            $response = array();
            if ($region_id != 0) {
                $hospital_list = RegionHospitals::getAllRegionHospitals($region_id);
            } else {
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $hospital_list = RegionHospitals::getAllSystemHospitals($system_user->health_system_id);
            }

            $contract_type_options = array();

            //find contract type data from payment type
            $contract_type_data = ContractType::getAllSystemContractTypes($user_id, $group_id, $region_id, $hospital_id, $payment_type_id);
            foreach ($contract_type_data as $key => $value) {
                if (!(array_key_exists($key, $contract_type_options))) {
                    $contract_type_options[$key] = ['name' => $value, 'id' => $key];
                }
            }

            asort($contract_type_options);

            $response['contract_types'] = array_values($contract_type_options);
            return $response;
        }
    }


    public function getProviderProfileReports($group)
    {
        //if(!is_health_system_user() && !is_health_system_region_user())
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);


        $default = [0 => 'All'];
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
            $hospital_list = $default + RegionHospitals::getAllSystemHospitals($system_user->health_system_id)->toArray();
            $system = HealthSystem::findOrFail($system_user->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
                ->where('health_system_regions.health_system_id', '=', $system_user->health_system_id)
                ->get();
        } else {
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $region = HealthSystemRegion::findOrFail($region_user->health_system_region_id);
            $regions = HealthSystemRegion::where('id', '=', $region_user->health_system_region_id)->pluck('region_name', 'id');
            $hospital_list = $default + RegionHospitals::getAllRegionHospitals($region_user->health_system_region_id)->toArray();
            $system = HealthSystem::findOrFail($region->health_system_id);
            $hospitals = RegionHospitals::select('hospitals.id as id')
                ->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->where('region_hospitals.region_id', '=', $region_user->health_system_region_id)
                ->get();
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system) {
                return $query->where('health_system_id', '=', $system->id)
                    ->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::PROVIDER_PROFILE_REPORTS);
            });
        } else {
            $data = $this->query('HealthSystemReport', $options, function ($query, $options) use ($system, $region) {
                return $query->where('health_system_id', '=', $system->id)
                    ->where("health_system_region_id", "=", $region->id)
                    ->where("created_by_user_id", "=", Auth::user()->id)
                    ->where("report_type", "=", HealthSystemReport::PROVIDER_PROFILE_REPORTS);
            });
        }
        $data['group'] = $group;
        $data['table'] = View::make('health_system/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['system'] = $system;
        $data['regions'] = $regions;
        if ($group == Group::HEALTH_SYSTEM_USER) {
            $data["region_id"] = Request::input("region", 0);
        } else {
            $data['region'] = $region;
            $data["region_id"] = $region->id;
        }
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);

        if ($data["facility"] == 0) {
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospitals->toArray());
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospitals->toArray(), $agreements, $start_date, $end_date);
        } else {
            $hospital[] = $data["facility"];
            $data['agreements'] = Agreement::getHospitalAgreementDataForHealthSystemReports($hospital);
            $data["physicians"] = Physician::getPhysicianDataForHealthSystemReports($hospital, $agreements, $start_date, $end_date);
        }

        if ($agreements != null) {
            foreach ($agreements as $agreement) {
                if (isset($start_date[$agreement]) != null && isset($end_date[$agreement]) != null) {
                    $start = $start_date[$agreement];
                    $end = $end_date[$agreement];
                    $data['selected_start_date'][$agreement] = $start;
                    $data['selected_end_date'][$agreement] = $end;
                } else {
                    $data['selected_start_date'][$agreement] = isset($start_date[$agreement]);
                    $data['selected_end_date'][$agreement] = isset($end_date[$agreement]);
                }
            }
        }

        $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        $data['form'] = View::make('layouts/_health_system_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system/providerProfile')->with($data);
    }

    public function postProviderProfileReports($group)
    {
        if (!($group == Group::HEALTH_SYSTEM_USER) && !($group == Group::HEALTH_SYSTEM_REGION_USER))
            App::abort(403);
        $result = HealthSystemReport::getProviderProfileReportData($group);
        return $result;
    }

    public function getAgreementDataByAjaxForHealthSystem($group_id)
    {
        if (Request::ajax()) {
            $data['agreement_data'] = HealthSystem::agreements_data_for_health_system_users(Auth::user()->id, $group_id);
            if ($group_id == Group::HEALTH_SYSTEM_USER) {
                return View::make('dashboard/region_data_ajax')->with($data);
            } else {
                return View::make('dashboard/agreement_data_ajax')->with($data);
            }
        }
    }


}

?>
