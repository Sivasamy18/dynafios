<?php
namespace App\Http\Controllers;

use App\HealthSystemRegion;
use App\HealthSystemRegionUsers;
use App\HealthSystem;
use App\Group;
use App\RegionHospitals;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_user;

class HealthSystemRegionController extends ResourceController
{
    protected $requireAuth = true;

    public function getShow($systemId, $id)
    {
        $data = HealthSystemRegion::getDetails($id, $systemId);

        return View::make('health_system_region/show')->with($data);
    }

    public function getEdit($systemId, $id)
    {
        $region = HealthSystemRegion::findOrFail($id);
        $system = HealthSystem::findOrFail($systemId);

        if (!is_super_user())
            App::abort(403);


        $data = [
            "region" => $region,
            "system" => $system
        ];

        return View::make('health_system_region/edit')->with($data);
    }

    public function postEdit($systemId, $id)
    {
        if (!is_super_user())
            App::abort(403);


        $result = HealthSystemRegion::editRegion($id, $systemId);
        return $result;
    }

    public function getUsers($systemId, $id)
    {
        $system = HealthSystem::findOrFail($systemId);
        $region = HealthSystemRegion::findOrFail($id);

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

        $data = $this->query('User', $options, function ($query, $options) use ($region) {
            return $query->select('users.*')
                ->join('health_system_region_users', 'health_system_region_users.user_id', '=', 'users.id')
                ->where('health_system_region_users.health_system_region_id', '=', $region->id)
                ->whereNull('health_system_region_users.deleted_at');
        });

        $data['system'] = $system;
        $data['region'] = $region;
        $data['table'] = View::make('health_system_region/_users')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system_region/users')->with($data);
    }

    public function getCreateUser($systemId, $id)
    {
        $system = HealthSystem::findOrFail($systemId);
        $region = HealthSystemRegion::findOrFail($id);

        if (is_super_user()) {
            $groups = [
                '8' => Group::findOrFail(8)->name
            ];

            return View::make('health_system_region/create_user')->with([
                'system' => $system,
                'region' => $region,
                'groups' => $groups
            ]);
        } else {
            App::abort(403);
        }
    }

    public function postCreateUser($systemId, $id)
    {
        $system = HealthSystem::findOrFail($systemId);
        $region = HealthSystemRegion::findOrFail($id);

        if (is_super_user()) {
            $result = HealthSystemRegionUsers::createRegionUser($region, $system);
            return $result;
        } else {
            App::abort(403);
        }
    }

    public function getRegionHospitals($systemId, $id)
    {
        $system = HealthSystem::findOrFail($systemId);
        $region = HealthSystemRegion::findOrFail($id);

        if (!is_super_user())
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['npi', 'name', 'state_id', 'expiration', 'created_at']
        ];

        $data = $this->query('Hospital', $options, function ($query, $options) use ($region) {
            return $query->select('hospitals.*')
                ->join('region_hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
                ->where('region_hospitals.region_id', '=', $region->id)
                ->whereNull('region_hospitals.deleted_at');
        });

        $data['system'] = $system;
        $data['region'] = $region;
        $data['table'] = View::make('health_system_region/_hospitals')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('health_system_region/hospitals')->with($data);
    }

    public function getAddRegionHospital($systemId, $id)
    {
        if (!is_super_user())
            App::abort(403);

        $data['system'] = HealthSystem::findOrFail($systemId);
        $data['region'] = HealthSystemRegion::findOrFail($id);
        $data['hospitals'] = RegionHospitals::gethospitalsToAdd();
        return View::make('health_system_region/addHospital')->with($data);
    }

    public function postAddRegionHospital($systemId, $id)
    {
        if (is_super_user()) {
            $data['system'] = HealthSystem::findOrFail($systemId);
            $data['region'] = HealthSystemRegion::findOrFail($id);
            $result = RegionHospitals::addRegionHospital($systemId, $id);
            return $result;
        } else {
            App::abort(403);
        }
    }

    public function getDisassociateRegionHospital($systemId, $id, $h_id)
    {
        if (is_super_user()) {
            $result = RegionHospitals::disassociateRegionHospital($systemId, $id, $h_id);
            return $result;
        } else {
            App::abort(403);
        }
    }

    public function getDeleteRegion($systemId, $id)
    {
        if (!is_super_user())
            App::abort(403);

        $result = HealthSystemRegion::deleteRegion($id);

        if (!$result) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system_region.delete_error')])
                ->withInput();
        } else {
            return Redirect::route('healthSystem.regions', $systemId)
                ->with(['success' => Lang::get('health_system_region.delete_success')]);
        }
    }

    //function to load view of add existing hospital user as a healthsystem region user
    public function getAddUser($systemId, $id)
    {
        $system = HealthSystem::findOrFail($systemId);
        $region = HealthSystemRegion::findOrFail($id);

        if (is_super_user()) {
            $groups = [
                '8' => Group::findOrFail(8)->name
            ];

            return View::make('health_system_region/add_user')->with([
                'system' => $system,
                'region' => $region,
                'groups' => $groups
            ]);
        } else {
            App::abort(403);
        }
    }

    //function to save existing hospital user as a healthsystem user
    public function postAddUser($systemId, $id)
    {
        //$system = HealthSystem::findOrFail($id);
        $email = Request::input('email');
        $group = Request::input('group');
        $region = 0;

        if (is_super_user()) {
            $result = HealthSystemRegionUsers::addSystemRegionUser($id, $email, $group);
            if ($result['response'] == 'error') {
                return Redirect::back()
                    ->with(['error' => $result['msg']]);
            } else {
                return Redirect::route('healthSystemRegion.users', [$systemId, $id])
                    ->with(['success' => $result['msg']]);
            }
        } else {
            App::abort(403);
        }

    }


}

?>
