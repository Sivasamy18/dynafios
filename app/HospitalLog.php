<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Log;

class HospitalLog extends Model
{
    protected $table = 'hospital_logs';

    public static function get_hospital_logs($hospital_ids)
    {
        $results = self::select(DB::raw('SUM(logs) as total_logs'), DB::raw('SUM(rejected_logs) as rejected_logs'))
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->first();

        return $results;
    }

    public static function get_hospital_logs_for_all_hospitals()
    {
        $results = self::select(DB::raw('SUM(logs) as total_logs'), DB::raw('SUM(rejected_logs) as rejected_logs'))
            ->first();

        return $results;
    }

    public static function get_hospital_logs_for_popup($hospital_id)
    {
        $results = self::select('*')
            ->whereIn('hospital_logs.hospital_id', $hospital_id)
            ->get();

        return $results;
    }

    public static function get_practice_logs($hospital_ids, $practice_id)
    {
        if ($practice_id == 0) {
            $results = self::select('hospital_logs.hospital_id', 'hospital_logs.practice_id', DB::raw('SUM(hospital_logs.logs) as total_logs'), DB::raw('SUM(hospital_logs.rejected_logs) as rejected_logs'))
                ->whereIn('hospital_logs.hospital_id', $hospital_ids)
                ->groupBy('hospital_logs.practice_id')
                ->distinct()
                ->get();
        } else {
            $results = self::select('hospital_logs.hospital_id', 'hospital_logs.practice_id', DB::raw('SUM(hospital_logs.logs) as total_logs'), DB::raw('SUM(hospital_logs.rejected_logs) as rejected_logs'))
                ->whereIn('hospital_logs.hospital_id', $hospital_ids)
                ->where('hospital_logs.practice_id', '=', $practice_id)
                ->groupBy('hospital_logs.practice_id')
                ->distinct()
                ->get();
        }

        return $results;
    }

    public static function get_physicians_logs($hospital_ids)
    {
        $results = self::select('hospital_logs.hospital_id', 'hospital_logs.physician_id', DB::raw('SUM(hospital_logs.logs) as total_logs'), DB::raw('SUM(hospital_logs.rejected_logs) as rejected_logs'))
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->groupBy('hospital_logs.physician_id')
            ->distinct()
            ->get();

        return $results;
    }

    public static function get_physician_logs_for_popup($hospital_ids, $physician_id)
    {
        $results = self::select('*')
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->where('hospital_logs.physician_id', '=', $physician_id)
            ->distinct()
            ->get();

        return $results;
    }

    public static function get_contracts($hospital_ids)
    {
        $results = self::select('*')
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->where('hospital_logs.rejected_logs', '!=', 0)
            ->distinct()
            ->get();

        return $results;
    }

    public static function get_agreements($hospital_ids, $agreement_list)
    {
        $results = self::select('*')
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->whereIn('hospital_logs.agreement_id', $agreement_list)
            ->distinct()
            ->get();

        return $results;
    }

    public static function get_contracts_type_logs($hospital_ids){
        $results = self::select('contract_id', DB::raw('SUM(logs) as logs'), DB::raw('SUM(rejected_logs) as rejected_logs'))
            ->whereIn('hospital_logs.hospital_id', $hospital_ids)
            ->groupBy('contract_id')
            ->get();

        foreach ($results as $key => $result) {
            if ($result->rejected_logs == 0) {
                unset($results[$key]);
            }
        }

        return $results;
    }
}
