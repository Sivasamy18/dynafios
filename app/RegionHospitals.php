<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Controllers\Validations\HealthSystemRegionValidation;
use Request;
use Redirect;
use Lang;

class RegionHospitals extends Model
{

    use SoftDeletes;

    protected $table = 'region_hospitals';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function gethospitalsToAdd()
    {
        $default = ["" => "Select Hospital"];
        $present = self::pluck('hospital_id');
        if (count($present) == 0) {
            $present[] = 0;
        }
        $hospital = Hospital::where('hospitals.archived', '=', false)
            ->whereNotIn('hospitals.id', $present)
            ->orderBy('hospitals.name')->pluck('name', 'id');
        return $default + ($hospital->toArray());
    }

    public static function addRegionHospital($systemId, $id)
    {
        $data['system'] = HealthSystem::findOrFail($systemId);
        $data['region'] = HealthSystemRegion::findOrFail($id);
        $validation = new HealthSystemRegionValidation();
        if (!$validation->validateAddHospital(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }
        $accociate = new RegionHospitals();
        $accociate->hospital_id = Request::input('hospital');
        $accociate->region_id = $id;
        if (!$accociate->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('health_system_region.hospital_add_error')])
                ->withInput();
        }
        // custome invoice for life point by akash
        if ($systemId == 6) {
            Hospital::where('id', '=', Request::input('hospital'))->update(['invoice_type' => 1]);
        }
        return Redirect::route('healthSystemRegion.hospitals', [$systemId, $id])->with([
            'success' => Lang::get('health_system_region.hospital_add_success')
        ]);
    }

    public static function disassociateRegionHospital($systemId, $id, $hospital_id)
    {
        $region_hospital = self::where('region_id', '=', $id)->where('hospital_id', '=', $hospital_id)->first();

        // if (count($region_hospital) == 0) { //old condition
        if ($region_hospital == null) {
            return Redirect::back()->with(['error' => Lang::get('health_system_region.disassociate_error')]);
        } else {
            $region_hospital->delete();
            Hospital::where('id', '=', $hospital_id)->update(['invoice_type' => 0]); // set to default invoice type after disassociantion
        }

        return Redirect::route('healthSystemRegion.hospitals', [$systemId, $id])->with(['success' => Lang::get('health_system_region.disassociate_success')]);
    }

    public static function getAllSystemHospitals($systemId)
    {
        $system_hospital_list = self::select('hospitals.name as name', 'hospitals.id as id')->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->join('health_system_regions', 'health_system_regions.id', '=', 'region_hospitals.region_id')
            ->where('health_system_regions.health_system_id', '=', $systemId)
            ->orderBy('hospitals.name')
            ->pluck('name', 'id');
        return $system_hospital_list;
    }

    public static function getAllRegionHospitals($region_id)
    {
        $system_hospital_list = self::select('hospitals.name as name', 'hospitals.id as id')->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->where('region_hospitals.region_id', '=', $region_id)
            ->orderBy('hospitals.name')
            ->pluck('name', 'id');
        return $system_hospital_list;
    }

}
