<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\Hospital;
use App\Practice;
use App\Physician;
use App\HealthSystemUsers;
use App\HealthSystemRegionUsers;
use App\HealthSystem;
use App\HealthSystemRegion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_user;

class SearchController extends BaseController
{
    public function postQuery()
    {
        $query = Request::input('query');

        $data['users'] = $this->searchUsers($query);
        $data['hospitals'] = $this->searchHospitals($query);
        $data['practices'] = $this->searchPractices($query);
        $physicians = $this->searchPhysicians($query);

        //issue2: navigation incorrect(wrong practice) when selecting or editing physician under perticular hospital by 1254 :15022021
        $listphysicians = array();
        foreach ($physicians as $physician) {
            //drop column practice_id from table 'physicians' changes by 1254 : codereview
//                $practiceid = DB::table("physician_practices")
//                                ->select("physician_practices.practice_id")
//                                ->where("physician_practices.physician_id","=",$physician->id)
//                                ->where("physician_practices.hospital_id","=",$physician->hospital_id)
//                                ->whereNull("physician_practices.deleted_at")
//                                ->whereRaw("physician_practices.start_date <= now()")
//                                ->whereRaw("physician_practices.end_date >= now()")
//                                ->orderBy("physician_practices.start_date","DESC")
//                                ->get();
//
//                $physician->practice_id = $practiceid[0]->practice_id;
            $practice_name = DB::table("practices")->where('id', '=', $physician->practice_id)->value('name');
            $physician->practice_name = $practice_name;
            $listphysicians[] = $physician;
        }

        $data['physicians'] = $listphysicians;
        // $data['physicians'] = $this->searchPhysicians($query);
        $data['practice_managers'] = $this->searchPracticeManagers($query);
        $data['system_users'] = $this->searchSystemUsers($query);
        $data['systems'] = $this->searchSystems($query);
        $data['region_users'] = $this->searchRegionUsers($query);
        $data['regions'] = $this->searchRegions($query);
        $data['query'] = $query;
        $data['results'] = count($data['users']) + count($data['practice_managers']) + count($data['hospitals']) +
            count($data['practices']) + count($data['physicians']);
        //Physician to multiple hospital by 1254
        // $practice_id = DB::table("physicians")->select("physicians.practice_id")->where("email","=",$query)->first();
        // $data['practice_id'] = $practice_id->practice_id;
        $data['results_table'] = View::make('search/_results')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('search/query')->with($data);
    }

    private function searchUsers($query)
    {
        $users = User::select('hospitals.name', 'users.*');
        $users = $users->join('hospital_user', 'hospital_user.user_id', '=', 'users.id');
        $users = $users->join('hospitals', 'hospitals.id', '=', 'hospital_user.hospital_id');

        switch ($this->currentUser->group_id) {
            case Group::HOSPITAL_ADMIN:
            case Group::SUPER_HOSPITAL_USER:
            case Group::HOSPITAL_CFO:
                $users = $users->whereIn('users.id', function ($query) {
                    $query->select('hospital_user.user_id')
                        ->from('hospital_user')
                        ->whereIn('hospital_user.hospital_id', function ($query) {
                            $query->select('hospital_user.hospital_id')
                                ->from('hospital_user')
                                ->where('hospital_user.user_id', '=', $this->currentUser->id);
                        });
                });
                break;

            case Group::PRACTICE_MANAGER:
                $users = $users->whereIn('users.id', function ($query) {
                    $query->select('practice_user.user_id')
                        ->from('practice_user')
                        ->whereIn('practice_user.practice_id', function ($query) {
                            $query->select('practice_user.practice_id')
                                ->from('practice_user')
                                ->where('practice_user.user_id', '=', $this->currentUser->id);
                        });
                });
                break;
        }

        $users = $users->where(function ($users) use ($query) {
            $users->where('users.email', 'like', "%{$query}%")
                ->orWhere('users.first_name', 'like', "%{$query}%")
                ->orWhere('users.last_name', 'like', "%{$query}%");
        });

        return $users->where('users.group_id', '!=', Group::Physicians)->get();
    }

    private function searchHospitals($query)
    {
        $hospitals = Hospital::select('hospitals.*');

        switch ($this->currentUser->group_id) {
            case Group::HOSPITAL_ADMIN:
            case Group::SUPER_HOSPITAL_USER:
                $hospitals = $hospitals->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                    ->where('hospital_user.user_id', '=', $this->currentUser->id);
                break;

            case Group::PRACTICE_MANAGER:
                return [];
        }

        $hospitals = $hospitals->where(function ($hospitals) use ($query) {
            $hospitals->where('hospitals.npi', 'like', "%{$query}%")
                ->orWhere('hospitals.name', 'like', "%{$query}%")
                ->groupBy('hospitals.id');
        });

        return $hospitals->get();
    }

    private function searchPractices($query)
    {
        $practices = Practice::select('practices.*');

        switch ($this->currentUser->group_id) {
            case Group::HOSPITAL_ADMIN:
            case Group::SUPER_HOSPITAL_USER:
                $practices = $practices->whereIn('practices.id', function ($query) {
                    $query->select('hospital_user.hospital_id')
                        ->from('hospital_user')
                        ->where('hospital_user.user_id', '=', $this->currentUser->id);
                });
                break;

            case Group::PRACTICE_MANAGER:
                $practices = $practices
                    ->join('practice_user', 'practice_user.practice_id', '=', 'practices.id')
                    ->where('practice_user.user_id', '=', $this->currentUser->id);
                break;
        }

        $practices = $practices->where(function ($practices) use ($query) {
            $practices->where('practices.npi', 'like', "%{$query}%")
                ->orWhere('practices.name', 'like', "%{$query}%")
                ->groupBy('practices.id');
        });

        return $practices->get();
    }

    private function searchPhysicians($query)
    {
        $physicians = Physician::select('hospitals.name', 'hospital_id', 'physicians.*', 'physician_practices.practice_id');
        // $physicians = $physicians->join('practices', 'practices.id', '=', 'physicians.practice_id')
        // ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id');

        //issue : display multiuple hospital for one to many by 1254 :15022021
        $physicians = $physicians->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('hospitals', 'hospitals.id', '=', 'physician_practices.hospital_id')
            //issue : showing physician also after deleting from hospital for searching :  one to many by 1254 :15022021
            ->whereNull('physician_practices.deleted_at')
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->orderBy("physician_practices.start_date", "DESC")
            ->distinct();


        switch ($this->currentUser->group_id) {
            case Group::HOSPITAL_ADMIN:
            case Group::SUPER_HOSPITAL_USER:
                $physicians = $physicians
                    ->whereIn('hospitals.id', function ($query) {
                        $query->select('hospital_id')
                            ->from('hospital_user')
                            ->where('hospital_user.user_id', '=', $this->currentUser->id);
                    });
                break;

            case Group::PRACTICE_MANAGER:
                $physicians = $physicians
                    ->whereIn('physician_practices.practice_id', function ($query) {
                        $query->select('practice_id')
                            ->from('practice_user')
                            ->where('practice_user.user_id', '=', $this->currentUser->id);
                    });
                break;
        }

        $physicians = $physicians->where(function ($physicians) use ($query) {
            $physicians->where('physicians.npi', 'like', "%{$query}%")
                ->orWhere('physicians.email', 'like', "%{$query}%")
                ->orWhere('physicians.first_name', 'like', "%{$query}%")
                ->orWhere('physicians.last_name', 'like', "%{$query}%");
        });

        return $physicians->get();
    }

    private function searchPracticeManagers($query)
    {
        $users = User::select('practices.name', 'users.*');
        $users = $users->join('practice_user', 'practice_user.user_id', '=', 'users.id');
        $users = $users->join('practices', 'practices.id', '=', 'practice_user.practice_id');

        switch ($this->currentUser->group_id) {
            case Group::HOSPITAL_ADMIN:
            case Group::SUPER_HOSPITAL_USER:
            case Group::HOSPITAL_CFO:
                $users = $users->whereIn('users.id', function ($query) {
                    $query->select('practice_user.user_id')
                        ->from('practice_user')
                        ->whereIn('practice_user.practice_id', function ($query) {
                            $query->select('practice_user.practice_id')
                                ->from('practice_user')
                                ->where('practice_user.user_id', '=', $this->currentUser->id);
                        });
                });
                break;

            case Group::PRACTICE_MANAGER:
                $users = $users->whereIn('users.id', function ($query) {
                    $query->select('practice_user.user_id')
                        ->from('practice_user')
                        ->whereIn('practice_user.practice_id', function ($query) {
                            $query->select('practice_user.practice_id')
                                ->from('practice_user')
                                ->where('practice_user.user_id', '=', $this->currentUser->id);
                        });
                });
                break;
        }

        $users = $users->where(function ($users) use ($query) {
            $users->where('users.email', 'like', "%{$query}%")
                ->orWhere('users.first_name', 'like', "%{$query}%")
                ->orWhere('users.last_name', 'like', "%{$query}%");
        });

        return $users->where('users.group_id', '!=', Group::Physicians)->get();
    }

    private function searchSystemUsers($query)
    {
        if (is_super_user()) {
            $users = HealthSystemUsers::searchUsers($query);
            return $users;
        } else {
            return [];
        }
    }

    private function searchSystems($query)
    {
        if (is_super_user()) {
            $systems = HealthSystem::searchSystems($query);
            return $systems;
        } else {
            return [];
        }
    }

    private function searchRegionUsers($query)
    {
        if (is_super_user()) {
            $users = HealthSystemRegionUsers::searchUsers($query);
            return $users;
        } else {
            return [];
        }
    }

    private function searchRegions($query)
    {
        if (is_super_user()) {
            $regions = HealthSystemRegion::searchRegions($query);
            return $regions;
        } else {
            return [];
        }
    }
}
