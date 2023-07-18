<?php
namespace Dynafios\Managers;

use App\Action;
use App\Agreement;
use App\Contract;
use App\customClasses\PaymentFrequencyFactoryClass;
use App\Jobs\UpdateLogCounts;
use App\Jobs\UpdatePaymentStatusDashboard;
use App\PaymentType;
use App\Physician;
use App\PhysicianLogHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Request;
use App\ContractDeadlineDays;
use App\PhysicianLog;
use DateTime;
use DateTimeZone;

class PhysicianLogManager {

    /*
     * Below function is used for submitting physician logs from web as well as mobile application.
     */
    public static function postSaveLog()
    {
        try {
            $action_id = Request::input("action");
            $shift = Request::input("shift");
            $duration = Request::input("duration");
            $log_details = Request::input("notes");
            $physician_id = Request::input("physicianId");
            $physician = Physician::findOrFail($physician_id);
            $user_id=Auth::user()->id;
            $start_time = Request::input("start_time", "");
            $end_time = Request::input("end_time", "");
            $hospital_id =  Request::input("hospitalId");
            if(Request::input("zoneName")!='') {
                if(!strtotime(Request::input("current"))) {
                    $zone = new DateTime(strtotime(Request::input("current")));
                }
                else {
                    $zone = new DateTime(false);
                }
                //$zone->setTimezone(new DateTimeZone('Pacific/Chatham'));
                $zone->setTimezone(new DateTimeZone(Request::input("zoneName")));
                //$zone = $zone->format('T');
                //Log::info($zone->format('T'));
                Request::merge(['timeZone' => Request::input("timeStamp") . ' ' .$zone->format('T')]);
            }else{
                Request::merge(['timeZone' => '']);
            }

            /* TO DO */
            // if (Request::input("action") === "" || Request::input("action") == null || Request::input("action") == -1) {
            if(Request::input("action") === "" || Request::input("action") == null){
                return "both_actions_empty_error";
            }
            if (Request::input("action") == -1) {
                $action = new Action;
                $action->name = Request::input("action_name");
                $action->contract_type_id = Request::input("contract_type_id");
                $action->payment_type_id = Request::input("payment_type_id");
                $action->action_type_id = 5;
                $action->save();
                $action_id = $action->id;
            }
            $contract_id = Request::input("contractId");
            $contract=Contract::findOrFail($contract_id);
            $getAgreementID = DB::table('contracts')
                ->where('id', '=', $contract_id)
                ->pluck('agreement_id');
            $agreement = Agreement::findOrFail($getAgreementID);
            $start_date=$agreement->first()->start_date;
            //$end_date=$agreement->end_date;
            $end_date=$contract->manual_contract_end_date;
            $selected_dates = Request::input("dates");
            foreach ($selected_dates as $selected_date) {

                //check for contract dealine option & contract deadline days
                if ((mysql_date($selected_date) >= $start_date) && (mysql_date($selected_date) <= $end_date)) {
                    if($contract->deadline_option == '1')
                    {
                        $contract_deadline_days_number= ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_Deadline_number_string='-'.$contract_deadline_days_number->contract_deadline_days.' days';
                    }
                    else
                    {
                        $contract_Deadline_number_string='-365 days';
                    }

                    // Start - Server side validation for approved months logs not allowed
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->first()->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement->first());
                    $period_dates = $res_pay_frequency['date_range_with_start_end_date'];

                    foreach($period_dates as $dates_obj) {

                        if (strtotime(mysql_date($selected_date)) >= strtotime (mysql_date($dates_obj['start_date'])) && strtotime(mysql_date($selected_date)) <= strtotime (mysql_date($dates_obj['end_date']))){
                            $check_approved_period = PhysicianLog::select("physician_logs.*")
                                ->whereNull('physician_logs.deleted_at')
                                ->where('physician_logs.contract_id', '=', $contract->id)
                                ->where("physician_logs.physician_id", "=", $physician_id)
                                ->whereBetween('physician_logs.date', [mysql_date($dates_obj['start_date']), mysql_date($dates_obj['end_date'])])
                                ->where('next_approver_level', '!=', 0)
                                ->where('next_approver_user', '!=', 0)
                                ->get();
                            if (count($check_approved_period) > 0) {
                                log::debug("Logs already present");
                                return "Can not select date from the range of approved logs. (".$dates_obj['start_date']. "-" .$dates_obj['end_date'].")";
                            }
                        }
                    }
                    // End - Server side validation for approved months logs not allowed

                    // Start - Logs should not go over allowed duration on the particular date validation
                    $logdata= PhysicianLog::select(DB::raw('SUM(duration) as duration'))
                        ->where('contract_id', '=', $contract->id)
                        ->where('physician_id', '=', $physician_id)
                        ->where('date', '=', mysql_date($selected_date))
                        ->whereNull('deleted_at')
                        ->get();

                    if(count($logdata) > 0){
                        if( ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->partial_hours == 0){

                            $allowed_duration = DB::table("action_contract")
                                ->select("hours")
                                ->where("contract_id", "=", $contract->id)
                                ->where("action_id", "=", $action_id)
                                ->first();

                            if($logdata[0]->duration > $allowed_duration->hours || $logdata[0]->duration + $duration > $allowed_duration->hours){
                                $allowed_duration_for_log = $allowed_duration->hours - $logdata[0]->duration;
                                return "Excess Duration for the date $selected_date. <br/>Allowed duration is only $allowed_duration_for_log hour.";
                            }
                        }else{
                            if($contract->payment_type_id != PaymentType::PER_UNIT){
                                if($logdata[0]->duration > Contract::ALLOWED_MAX_HOURS_PER_DAY || $logdata[0]->duration + $duration > Contract::ALLOWED_MAX_HOURS_PER_DAY){
                                    $allowed_duration_for_log = Contract::ALLOWED_MAX_HOURS_PER_DAY - $logdata[0]->duration;
                                    return "Excess Duration for the date $selected_date. <br/> Allowed duration is only $allowed_duration_for_log hour.";
                                }
                            }
                        }
                    }
                    // End - Logs should not go over allowed duration on the particular date validation

                    if (strtotime(mysql_date($selected_date)) > strtotime ($contract_Deadline_number_string)) {
                        //Check hours log is under 24 and for directership it also under annual cap
                        $physician_log = new PhysicianLog();
                        $checkHours = $physician_log->getHoursCheck($contract_id,$physician_id,$selected_date,$duration);

                        if ($checkHours === 'Under 24' || $contract->payment_type_id === 4) {
                            if(($duration < 0.25 || !is_numeric($duration)) && ($start_time == "" && $end_time == "") && ($contract->payment_type_id != PaymentType::TIME_STUDY)) {
                                return "no_duration";
                            } else {
                                return self::saveLog($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
                            }
                        } else {
                            return $checkHours;
                        }
                    }else{
                        return "Excess 365";
                    }
                }
            }
        } catch (\Exception $ex){
            Log::info("PhysicianLogManager postSaveLog :" . $ex->getMessage());
        }
    }

    public function saveLog($action_id,$shift,$log_details,$physician,$contract_id,$selected_date,$duration,$user_id, $start_time, $end_time){

        if($start_time != "" && $end_time != ""){
            $start_time = new DateTime($selected_date . " " . $start_time);  // $date->format('Y-m-d G:i:s');
            $end_time = new DateTime($selected_date . " " . $end_time);   // $date->format('Y/m/d G:i:s');

            if($start_time >= $end_time){
                return "Start And End Time";
            }

            $logs = PhysicianLog::select('*')
                ->where('contract_id', '=', $contract_id)
                ->where('physician_id', '=', $physician->id)
                ->where('date', '=', mysql_date($selected_date))
                ->where('start_time', '!=', "0000-00-00 00:00:00")
                ->where('end_time', '!=', "0000-00-00 00:00:00")
                ->get();

            if(count($logs) > 0){
                foreach($logs as $logs){
                    $date = new DateTime($logs->start_time);
                    $logs_start_time = $date;   // $date->format('Y-m-d G:i:s');

                    $date = new DateTime($logs->end_time);
                    $logs_end_time = $date;     //$date->format('Y-m-d G:i:s');

                    $n_start_time = $start_time;    // ->format('Y-m-d G:i:s');
                    $n_end_time = $end_time;        // ->format('Y-m-d G:i:s');

                    if($n_start_time <= $logs_start_time && $n_end_time <= $logs_start_time){
                        // false
                    } else if($n_start_time >= $logs_end_time){
                        // false
                    } else if($n_start_time >= $logs_start_time && $n_start_time <= $logs_end_time){
                        return "Log Exist";
                    } else if($n_end_time >= $logs_start_time && $n_end_time <= $logs_end_time){
                        return "Log Exist";
                    } else if($logs_start_time >= $n_start_time && $logs_end_time <= $n_end_time){
                        return "Log Exist";
                    }
                }
            }
        }

        $physician_logs = new PhysicianLog();
        $physician_logs->physician_id = $physician->id;
        $physician_log_history = new PhysicianLogHistory();
        $physician_log_history->physician_id =  $physician->id;

        $agreement_obj = Contract::select('agreements.hospital_id as hospital_id', 'agreements.id as agreement_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->where('contracts.id', '=', $contract_id)
            ->first();

        $practice_info = DB::table('contracts')
            ->select('physician_contracts.practice_id','contracts.contract_name_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
//            ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'contracts.practice_id')
            ->join('physician_practice_history', function($join)
            {
                $join->on('physician_practice_history.physician_id', '=', 'physician_contracts.physician_id');
                $join->on('physician_practice_history.practice_id', '=', 'physician_contracts.practice_id');

            })
            ->where('physician_contracts.physician_id', '=', $physician->id)
            ->where('contracts.id', '=', $contract_id)
            ->where('physician_practice_history.start_date', '<=', mysql_date($selected_date))
            ->where('physician_practice_history.end_date', '>=', mysql_date($selected_date))
            ->first();


        if ($practice_info) {

            DB::beginTransaction();
            try {
                $physician_logs->practice_id = $practice_info->practice_id;
                $physician_log_history->practice_id = $practice_info->practice_id;

                $physician_logs->contract_id = $contract_id;
                $physician_logs->action_id = $action_id;
                $physician_logs->date = mysql_date($selected_date);
                $physician_logs->duration = $duration;
                $physician_logs->signature = 0;
                $physician_logs->details = $log_details;
                $physician_logs->approval_date = "0000-00-00";
                $physician_logs->entered_by = $user_id;
                if ($user_id != $physician->id) {
                    $physician_logs->entered_by_user_type = PhysicianLog::ENTERED_BY_USER;
                    $physician_log_history->entered_by_user_type = PhysicianLog::ENTERED_BY_USER;
                }
                else {
                    $physician_logs->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                    $physician_log_history->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                }
                $physician_logs->timeZone = Request::input("timeZone") != null ? Request::input("timeZone") : ''; /*save timezone*/
                $physician_logs->am_pm_flag = $shift;

                $contract=Contract::findOrFail($contract_id);

                if($contract->partial_hours ==1 && ($contract->payment_type_id ==3 || $contract->payment_type_id ==5)) {
                    $physician_logs->log_hours = ((1.00/$contract->partial_hours_calculation) * $duration); //(1/24)*$duration
                    $physician_log_history->log_hours = ((1.00/$contract->partial_hours_calculation) * $duration); //(1/24)*$duration
                }else{
                    $physician_logs->log_hours = ($duration);
                    $physician_log_history->log_hours = ($duration);
                }

                $physician_logs->start_time = $start_time;
                $physician_logs->end_time = $end_time;

                if($physician_logs->save()){
                    $physician_log_history->physician_log_id = $physician_logs->id;
                    $physician_log_history->contract_id = $contract_id;
                    $physician_log_history->action_id = $action_id;
                    $physician_log_history->date = mysql_date($selected_date);
                    $physician_log_history->signature = 0;
                    $physician_log_history->approval_date = "0000-00-00";
                    $physician_log_history->timeZone = Request::input("timeZone") != null ? Request::input("timeZone") : ''; /*save timezone*/
                    $physician_log_history->am_pm_flag = $shift;
                    $physician_log_history->duration = $duration;
                    $physician_log_history->details = $log_details;
                    $physician_log_history->entered_by = $user_id;
                    $physician_log_history->start_time = $start_time;
                    $physician_log_history->end_time = $end_time;

                    if($physician_log_history->save()){
                        /**
                         * Below function is use to set-up the payment_status_dashboard table with precalculated data for performance improvement.
                         */
                        // $update_pmt_status_dashboard = PaymentStatusDashboard::updatePaymentStatusDashboard($physician->id, $practice_info->practice_id, $contract_id, $practice_info->contract_name_id, $agreement_obj['hospital_id'], $agreement_obj['agreement_id'], $selected_date);
                        UpdatePaymentStatusDashboard::dispatch($physician->id, $practice_info->practice_id, $contract_id, $practice_info->contract_name_id, $agreement_obj['hospital_id'], $agreement_obj['agreement_id'], $selected_date);

                        UpdateLogCounts::dispatch($agreement_obj['hospital_id']);

                        DB::commit();
                    }
                }

                return "Success";

            } catch (\Exception $ex){
                DB::rollBack();
                Log::info("PhysicianLogManager saveLog :" . $ex->getMessage());
            }

        } else {
            return "practice_error";
        }
    }
}
