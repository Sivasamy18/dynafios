<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OnCallSchedules extends Model
{
    protected $table = 'on_call_schedule';
    public $timestamps = true;
    const AM = 1;
    const PM = 2;

    public static function getSchedule($contract)
    {
        $strat_date = date('Y-m-d', strtotime('-90 days'));
        $end_date = date('Y-m-d');
        $schedules = self::select('*')
            ->where("physician_id", "=", $contract->pivot->physician_id)
            ->where("practice_id", "=", $contract->pivot->practice_id)
            ->where("contract_id", "=", $contract->id)
            ->whereBetween("date", [mysql_date($strat_date), mysql_date($end_date)])
            ->get();
        $results = [];
        $duration_data = "";
        foreach ($schedules as $schedule) {

            //if ($contract->contract_type_id == ContractType::ON_CALL) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($schedule->physician_type == 1) {
                    $duration_data = "AM";
                } else {
                    $duration_data = "PM";
                }

            }

            $results[] = [
                "date" => format_date($schedule->date),
                "duration" => $duration_data
            ];
        }

        return $results;
    }

    private function getPhysicianSchedule($contract)
    {
        $strat_date = date('Y-m-d', strtotime('-90 days'));
        $end_date = date('Y-m-d');
        $schedules = $this->select('*')
            ->where("physician_id", "=", $contract->physician_id)
            ->where("contract_id", "=", $contract->id)
            ->whereBetween("date", [mysql_date($strat_date), mysql_date($end_date)])
            ->get();
        $results = [];
        $duration_data = "";
        foreach ($schedules as $schedule) {

            //if ($contract->contract_type_id == ContractType::ON_CALL) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($schedule->physician_type == 1) {
                    $duration_data = "AM";
                } else {
                    $duration_data = "PM";
                }
            }

            $results[] = [
                "date" => format_date($schedule->date),
                "duration" => $duration_data
            ];
        }

        return $results;
    }
}

?>