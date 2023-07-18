<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Controllers\Validations\HealthSystemRegionValidation;
use Request;
use Redirect;
use Lang;
use function App\Start\is_super_user;

class HealthSystemRegion extends Model
{

    use SoftDeletes;

    protected $table = 'health_system_regions';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function createSystemRegion($system)
    {
        DB::beginTransaction();
        $validation = new HealthSystemRegionValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $checkName = self::where('region_name', '=', Request::input('region_name'))->where('health_system_id', '=', $system->id)->get();
        if (count($checkName) > 0) {
            return Redirect::back()->withErrors(['region_name' => 'The health system region name has already been taken.'])->withInput();
        }

        $healthSystemRegion = new HealthSystemRegion();
        $healthSystemRegion->region_name = Request::input('region_name');
        $healthSystemRegion->health_system_id = $system->id;

        if (!$healthSystemRegion->save()) {
            DB::rollback();
            return Redirect::back()
                ->with(['error' => Lang::get('health_system_region.create_error')])
                ->withInput();
        }

        DB::commit();
        return Redirect::route('healthSystem.regions', $system->id)->with([
            'success' => Lang::get('health_system_region.create_success')
        ]);
    }

    public static function editRegion($id, $sid)
    {
        $system = HealthSystem::findOrFail($sid);
        $region = self::findOrFail($id);

        if (!is_super_user())
            App::abort(403);

        $validation = new HealthSystemRegionValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $checkName = self::where('id', '!=', $id)
            ->where('region_name', '=', Request::input('region_name'))
            ->where('health_system_id', '=', $system->id)
            ->get();
        if (count($checkName) > 0) {
            return Redirect::back()->withErrors(['region_name' => 'The health system region name has already been taken.'])->withInput();
        }

        $region->region_name = Request::input('region_name');

        if (!$region->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system_region.edit_error')])
                ->withInput();
        } else {

            return Redirect::route('healthSystemRegion.edit', [$system->id, $region->id])
                ->with(['success' => Lang::get('health_system_region.edit_success')]);
        }
    }

    public static function getDetails($id, $sid)
    {
        $data['system'] = HealthSystem::findOrFail($sid);
        $data['region'] = self::findOrFail($id);
        $data['region_users'] = HealthSystemRegionUsers::select('health_system_region_users.*', 'users.first_name as first_name', 'users.last_name as last_name')
            ->join('users', 'users.id', '=', 'health_system_region_users.user_id')
            ->where('health_system_region_users.health_system_region_id', '=', $id)
            ->whereNull('users.deleted_at')
            ->get();
        $data['region_hospitals'] = RegionHospitals::select('region_hospitals.*', 'hospitals.name as name')
            ->join('hospitals', 'hospitals.id', '=', 'region_hospitals.hospital_id')
            ->where('region_hospitals.region_id', '=', $id)
            ->get();
        return $data;
    }

    public static function deleteRegion($id)
    {
        DB::beginTransaction();
        $region = self::findOrFail($id);

        $associated_hospitals = RegionHospitals::where('region_id', '=', $id)->get();
        if (count($associated_hospitals) > 0) {
            $disassociate = RegionHospitals::where('region_id', '=', $id)->delete();
            if (!$disassociate) {
                DB::rollback();
                return false;
            }
        }

        $resion_users = HealthSystemRegionUsers::join('users', 'users.id', '=', 'health_system_region_users.user_id')
            ->where('health_system_region_users.health_system_region_id', '=', $id)
            ->pluck('user_id');
        if (count($resion_users) > 0) {
            $remove_users = HealthSystemRegionUsers::where('health_system_region_id', '=', $id)->delete();
            if (!$remove_users) {
                DB::rollback();
                return false;
            } else {
                $delete_users = User::whereIn('id', $resion_users)->delete();
                if (!$delete_users) {
                    DB::rollback();
                    return false;
                }
            }
        }
        if (!$region->delete()) {
            return false;
        }
        DB::commit();
        return true;
    }

    public static function searchRegions($query)
    {
        $systems = self::select('health_system_regions.*', 'health_system.health_system_name')
            ->join('health_system', 'health_system.id', '=', 'health_system_regions.health_system_id')
            ->where('health_system_regions.region_name', 'like', "%{$query}%")->get();
        return $systems;
    }

}
