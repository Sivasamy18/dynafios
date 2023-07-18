<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Validations\PhysicianValidation;
use App\Contract;
use Request;
use Redirect;
use Lang;
use Hash;
use DateTime;
use Auth;
use Log;

class PhysicianPractices extends Model
{
    use SoftDeletes;

    protected $table = 'physician_practices';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

//drop column practice_id from table 'physicians' changes by 1254

    public static function getHospitals($physician_id)
    {
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $hospital_ids = PhysicianPractices::select("physician_practices.hospital_id")
            ->where("physician_practices.physician_id", "=", $physician_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("deleted_at")
            ->distinct()
            ->get();


        $hospital_names = array();

        foreach ($hospital_ids as $hospital_id) {
            $hospitalname = Hospital::select("hospitals.name")
                ->where("hospitals.id", "=", $hospital_id->hospital_id)
                ->where("hospitals.archived", "=", 0)
                ->whereNull("hospitals.deleted_at")
                ->first();

            if ($hospitalname) {
                //drop column practice_id from table 'physicians' changes by 1254 : codereview
                $hospital_names[] = ["hopsitalid" => $hospital_id->hospital_id,
                    "hospitalid" => $hospital_id->hospital_id,
                    "hospitalname" => $hospitalname->name,
                    "hopsital_id" => $hospital_id->hospital_id,
                    "hospital_name" => $hospitalname->name,
                    "hospital_id" => $hospital_id->hospital_id
                ];
            }
        }
        return ($hospital_names);
    }

    public static function fetchHospitals($physician_id)
    {
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $hospital_ids = PhysicianPractices::select("hospitals.name as hospital_name", "physician_practices.hospital_id", "physician_practices.practice_id")
            ->join("hospitals", "hospitals.id", "=", "physician_practices.hospital_id")
            ->where("physician_practices.physician_id", "=", $physician_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            ->distinct()
            ->pluck("hospital_name", "hospital_id", "physician_practices.practice_id");


        //issue fixes for drop practice_id from physician : shows exception on rejected log button for hosptial having no contracts
        //return only hospital list whose has contract for physicians
        $physician = Physician::findOrFail($physician_id);
        foreach ($hospital_ids as $key => $hospital_id) {

            $active_contracts = $physician->contracts()
                ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                ->whereRaw("practices.hospital_id = $key")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
                ->distinct()
                ->get();

            if (count($active_contracts) > 0) {
                $hospitalids[$key] = $hospital_id;

            }
        }

        return $hospitalids;
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract', 'physician_id', 'physician_id')
            ->where('agreements.is_deleted', '=', 0);
    }

    /**
     * @param $hospital_id
     * @param $physician_id
     * @return array
     *
     *
     * #Uses
     * 1 - In physician login screen for contract dropdown.
     */
    public static function getContractsForHospital($hospital_id, $physician_id)
    {
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $physicianpractices = PhysicianPractices::where("physician_practices.hospital_id", "=", $hospital_id)
            ->where("physician_practices.physician_id", "=", $physician_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->pluck('physician_practices.practice_id')->toArray();

//                                             log::info("practice id",array($physicianpractices));

        //get contracts for practice id

        $contracts = array();
        $active_contracts = Contract::select("contracts.*", "practices.name as practice_name")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
//            ->join("physician_practices", "physician_practices.practice_id", "=", "contracts.practice_id")
            ->join('physician_practices', function ($join) {
                $join->on('physician_practices.physician_id', '=', 'physician_contracts.physician_id');
                $join->on('physician_practices.practice_id', '=', 'physician_contracts.practice_id');

            })
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id") // Sprint 6.1.13
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->whereNull("contracts.deleted_at")
            ->whereRaw("contracts.archived = false")
            ->whereIn("physician_contracts.practice_id", $physicianpractices)
            ->whereIn("physician_practices.practice_id", $physicianpractices)
            ->whereIn("practices.id", $physicianpractices)
            ->where("physician_contracts.physician_id", "=", $physician_id)
            ->whereNull("physician_contracts.deleted_at")
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->where('sorting_contract_names.is_active', '=', 1)
            ->orderBy("sorting_contract_names.practice_id", "ASC")
            ->orderBy("sorting_contract_names.sort_order", "ASC")   // Sprint 6.1.13
            ->distinct()
            ->get();


        $contract_name = array();
        foreach ($active_contracts as $contract) {
            $today = date('Y-m-d');
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                //$valid_upto=$contract->agreement_end_date; /* remove to add valid upto to contract*/
                $valid_upto = $contract->manual_contract_end_date;
            }

            if ($valid_upto > $today) {
                $contractname = DB::table('contracts')
                    ->select('contract_names.name as contract_name', 'contracts.payment_type_id')
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->where("contracts.id", "=", $contract->id)
                    ->distinct()
                    ->first();

                if ($contractname) {
                    $contract_name[] = ["contractid" => $contract->id,
                        "contractname" => $contractname->contract_name . " ($contract->practice_name)",
                        "paymenttype" => $contractname->payment_type_id
                    ];
                }

                // $contract_name=$contractname;
            }
        }


        return ($contract_name);

    }

    public static function updateExistingPracticePhysicianswithPhysician()
    {
        ini_set('max_execution_time', 6000);
        $practice_physicians = PhysicianPractices::select('physician_practices.physician_id as physician_id')
            ->whereNull("deleted_at")
            ->distinct()
            ->get();
        $all_physicians = Physician::select('physicians.id as physician_id', 'physicians.practice_id as practice_id')
            ->whereNotIn('physicians.id', $practice_physicians)
            ->withTrashed()
            ->distinct()
            ->get();
        //Log::info('Physicians',array($all_physicians));

        foreach ($all_physicians as $physician) {
            $user_id = Auth::user()->id;
            $physician_practice = PhysicianPracticeHistory::select('physician_practice_history.physician_id as physician_id',
                'physician_practice_history.practice_id as practice_id',
                'physician_practice_history.start_date as start_date',
                'physician_practice_history.end_date as end_date',
                'practices.hospital_id')
                ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
                //->where('physician_practice_history','=',$physician->physician_id);
                ->where('physician_practice_history.physician_id', '=', $physician->physician_id)->get();

            //Log::info('physician-practice',array($physician_practice));
            foreach ($physician_practice as $physician_practice) {
                $physician_practices = new PhysicianPractices();
                $physician_practices->physician_id = $physician_practice->physician_id;
                $physician_practices->practice_id = $physician_practice->practice_id;
                $physician_practices->hospital_id = $physician_practice->hospital_id;
                $physician_practices->start_date = $physician_practice->start_date;
                $physician_practices->end_date = $physician_practice->end_date;
                $physician_practices->created_by = $user_id;
                $physician_practices->save();
            }
            $practice_id = Physician::select('practice_id')->where('id', '=', $physician->physician_id)->first();
            if ($practice_id) {
                $update_contracts = DB::table('contracts')
                    ->where("physician_id", "=", $physician->physician_id)
                    ->update(['practice_id' => $practice_id->practice_id]);
            }
        }
        return 1;
    }

}
