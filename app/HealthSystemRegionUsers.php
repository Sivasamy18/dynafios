<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;
use Redirect;
use Lang;

class HealthSystemRegionUsers extends Model
{

    use SoftDeletes;

    protected $table = 'health_system_region_users';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function createRegionUser($region, $system)
    {
        DB::beginTransaction();
        $result = User::postCreate();
        if ($result["status"]) {
            $user = $result['data'];
            $attach = new HealthSystemRegionUsers();
            $attach->user_id = $user->id;
            $attach->health_system_region_id = $region->id;

            if (!$attach->save()) {
                DB::rollback();
                if (Request::input('type') != 'ajax') {
                    return Redirect::back()
                        ->with(['error' => Lang::get('health_system_region.create_user_error')])
                        ->withInput();
                } else {
                    return ['error' => Lang::get('health_system_region.create_user_error')];
                }
            }
            DB::commit();
            $data = [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $user->password_text,
                'hospital' => $system->health_system_name
            ];

            if (Request::input('type') != 'ajax') {
                return Redirect::route('healthSystemRegion.users', [$system->id, $region->id])->with([
                    'success' => Lang::get('health_system_region.create_user_success')
                ]);
            } else {
                return ['success' => Lang::get('health_system_region.create_user_success'),
                    'name' => "{$user->first_name} {$user->last_name}",
                    'user_id' => $user->id];
            }
        } else {
            DB::rollback();
            if (isset($result["validation"])) {
                if (Request::input('type') != 'ajax') {
                    return Redirect::back()->withErrors($result["validation"]->messages())->withInput();
                } else {
                    return $result["validation"]->messages();
                }
            } else {
                if (Request::input('type') != 'ajax') {
                    return Redirect::back()
                        ->with(['error' => Lang::get('health_system_region.create_user_error')])
                        ->withInput();
                } else {
                    return ['error' => Lang::get('health_system_region.create_user_error')];
                }
            }
        }
    }

    public static function searchUsers($query)
    {

        $users = User::select('health_system.health_system_name', 'health_system_regions.region_name', 'users.*');
        $users = $users->join('health_system_region_users', 'health_system_region_users.user_id', '=', 'users.id');
        $users = $users->join('health_system_regions', 'health_system_regions.id', '=', 'health_system_region_users.health_system_region_id');
        $users = $users->join('health_system', 'health_system.id', '=', 'health_system_regions.health_system_id');

        $users = $users->where(function ($users) use ($query) {
            $users->where('users.email', 'like', "%{$query}%")
                ->orWhere('users.first_name', 'like', "%{$query}%")
                ->orWhere('users.last_name', 'like', "%{$query}%");
        });

        return $users->where('users.group_id', '=', Group::HEALTH_SYSTEM_REGION_USER)->get();
    }

    //function for add existing user
    public static function addSystemRegionUser($region, $email, $group)
    {
        $user = User::select('users.id')->where('email', '=', $email)->first();
        //   $user_id=$user['id'];
        //   if($user_id=='') //Old condition changed by akash
        if ($user === null) {
            $result["response"] = "error";
            $result["msg"] = Lang::get('health_system_region.user_not_found_error');
        } else {
            $user_id = $user['id'];
            $healthSystem_user = HealthSystemUsers::select('id')->where('user_id', '=', $user_id)->whereNull('deleted_at')->first();
            if ($healthSystem_user) {
                $result["response"] = "error";
                $result["msg"] = Lang::get('health_system_region.system_user_found_error');
            } else {
                $healthSystem_region_user = HealthSystemRegionUsers::select('id')->where('user_id', '=', $user_id)->whereNull('deleted_at')->first();
                if ($healthSystem_region_user) {
                    $result["response"] = "error";
                    $result["msg"] = Lang::get('health_system_region.region_user_found_error');
                } else {
                    $healthSystem_region_user = new HealthSystemRegionUsers();
                    $healthSystem_region_user->user_id = $user_id;
                    $healthSystem_region_user->health_system_region_id = $region;

                    if (!$healthSystem_region_user->save()) {
                        $result["response"] = "error";
                        $result["msg"] = Lang::get('health_system_region.user_add_error');
                    } else {
                        $result["response"] = "success";
                        $result["msg"] = Lang::get('health_system_region.add_user_success');
                    }
                }
            }
        }
        return $result;
    }

}
