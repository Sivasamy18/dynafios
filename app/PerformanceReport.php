<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Request;
use DateTime;
use DateTimeZone;
use App\customClasses\PaymentFrequencyFactoryClass;

class PerformanceReport extends Model
{

    protected $table = 'performance_report';

    const PHYSICIAN_REPORT = 1;
    const APPROVER_REPORT = 2;

    public static function fetch_physician_data()
    {
        $result = array();
        $physician_data = array();
        $selected_agreements = Request::input('agreements');
        $selected_hospitals = Request::input('hospital', 0);

        $selected_hospitals = Hospital::where('id', '=', $selected_hospitals)->pluck("name", "id");

        foreach ($selected_hospitals as $hospital_id => $hospital_name) {

            $physician_data = array();
            $physicians = Physician::select('physicians.id as id', DB::raw(("concat(physicians.last_name, ', ', physicians.first_name) as name")))
                ->join("contracts", "contracts.physician_id", "=", "physicians.id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                //->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
                ->where('agreements.archived', '=', false)
                ->where('agreements.hospital_id', '=', $hospital_id)
                ->whereIn("agreements.id", [$selected_agreements]);

            $physicians = $physicians->orderBy("physicians.last_name")
                ->distinct()
                ->get();

            foreach ($physicians as $physician) {
                $contracts_with_approver = Contract::select('contracts.id as contract_id')
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                    ->where('agreements.archived', '=', false)
                    ->where('agreements.approval_process', '=', '1')
                    ->where('contracts.physician_id', '=', $physician->id)
                    ->whereIn("agreements.id", [$selected_agreements])
                    ->where('agreements.hospital_id', '=', $hospital_id)
                    ->distinct()
                    ->get();

                $contracts_without_approver = Contract::select('contracts.id as contract_id')
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                    ->where('agreements.archived', '=', false)
                    ->where('agreements.approval_process', '=', '0')
                    ->where('contracts.physician_id', '=', $physician->id)
                    ->whereIn("agreements.id", [$selected_agreements])
                    ->where('agreements.hospital_id', '=', $hospital_id)
                    ->distinct()
                    ->get();

                $avg_with_approver = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.role = 1  then DATEDIFF(log_approval.`approval_date`,CONCAT(YEAR(physician_logs.`created_at`),'-',MONTH(physician_logs.`created_at`)+1,'-01')) end) as average"),
                    "physician_id", DB::raw("'0' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level1 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 1  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 0 AND log_id = `physician_logs`.id and role = 1 AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'1' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level2 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 2  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 1 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'2' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level3 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 3  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 2 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'3' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level4 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 4  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 3 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'4' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level5 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 5  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 4 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'5' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver_level6 = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = 6  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = 5 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id", DB::raw("'6' as level"))
                    ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                    ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                    ->groupBy("physician_logs.physician_id");

                $avg_with_approver = $avg_with_approver->union($avg_with_approver_level1)
                    ->union($avg_with_approver_level2)->union($avg_with_approver_level3)
                    ->union($avg_with_approver_level4)->union($avg_with_approver_level4)
                    ->union($avg_with_approver_level5)->union($avg_with_approver_level6)
                    ->get();

                $physician_data[$physician->id]["level0"] = 0;
                $physician_data[$physician->id]["level1"] = 0;
                $physician_data[$physician->id]["level2"] = 0;
                $physician_data[$physician->id]["level3"] = 0;
                $physician_data[$physician->id]["level4"] = 0;
                $physician_data[$physician->id]["level5"] = 0;
                $physician_data[$physician->id]["level6"] = 0;

                foreach ($avg_with_approver as $details) {
                    $physician_data[$physician->id]["level" . $details->level] = $details->average;
                }

                if (count($contracts_without_approver) > 0) {
                    $avg_without_approver = PhysicianLog::select(DB::raw("AVG(DATEDIFF(`approval_date`,CONCAT(YEAR(`created_at`),\"-\",MONTH(`created_at`)+1,\"-01\"))) as average"), "physician_id")
                        ->whereIn("contract_id", $contracts_without_approver)
                        ->groupBy("physician_id")
                        ->pluck("average", "physician_id");

                    if ($avg_without_approver[$physician->id] > 0) {
                        if (array_key_exists($physician->id, $physician_data)) {
                            $physician_data[$physician->id]["level0"] = $physician_data[$physician->id]["level0"] > 0 ? formatNumber(($physician_data[$physician->id]["level0"] + $avg_without_approver[$physician->id]) / 2) : $avg_without_approver[$physician->id];
                        } else {
                            $physician_data[$physician->id]["level0"] = $avg_without_approver[$physician->id];
                            $physician_data[$physician->id]["level1"] = 0;
                            $physician_data[$physician->id]["level2"] = 0;
                            $physician_data[$physician->id]["level3"] = 0;
                            $physician_data[$physician->id]["level4"] = 0;
                            $physician_data[$physician->id]["level5"] = 0;
                            $physician_data[$physician->id]["level6"] = 0;
                        }
                    }
                }

                $all_contracts_physician = array();
                if (count($contracts_with_approver) > 0 && count($contracts_without_approver) > 0) {
                    $all_contracts_physician = array_merge($contracts_with_approver, $contracts_without_approver);
                } else if (count($contracts_with_approver) > 0) {
                    $all_contracts_physician = $contracts_with_approver;
                } else if (count($contracts_without_approver) > 0) {
                    $all_contracts_physician = $contracts_without_approver;
                } else {
                    $all_contracts_physician = array();
                }
                if (count($all_contracts_physician) > 0) {
                    // $avg_submit_pay = PhysicianLog::select(DB::raw("AVG(DATEDIFF(physician_logs.`approval_date`,(select `created_at` from amount_paid where start_date <= `physician_logs`.`created_at` AND end_date >= `physician_logs`.`created_at` limit 1)) end) as average"))
                    $avg_submit_pay = PhysicianLog::select(DB::raw("AVG(DATEDIFF(physician_logs.`approval_date`,(select `created_at` from amount_paid where start_date <= `physician_logs`.`created_at` AND end_date >= `physician_logs`.`created_at` limit 1))) as average"))
                        ->whereIn("physician_logs.contract_id", $all_contracts_physician)
                        ->groupBy("physician_logs.physician_id")->first();
                }

                /*$avg_submit_pay = PhysicianLog::select(DB::raw("AVG(DATEDIFF(physician_logs.`approval_date`,(select `created_at` from amount_paid where start_date = 5 AND log_id = `physician_logs`.id  AND approval_status = 1 limit 1)) end) as average"),
                    "physician_id","6 as level");*/

                $physician_data[$physician->id]["physician"] = $physician->name;
                $physician_data[$physician->id]["avg_submit_pay"] = $avg_submit_pay ? $avg_submit_pay->average : 0;
            }
            $result[$hospital_id]['physician_data'] = $physician_data;
            $result[$hospital_id]['hospital_name'] = $hospital_name;
            $result[$hospital_id]['localtimeZone'] = '';
        }

        return $result;
    }

    public static function fetch_physician_report_data()
    {
        ini_set('max_execution_time', 6000);
        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        $result = array();
        $physician_data = array();
        $selected_physicians = Request::input('physicians');
        $selected_agreements = Request::input('agreements');
        $selected_hospitals = Request::input('hospital', 0);
        $start_month = Request::input('start_month');
        $end_month = Request::input('end_month');
        $selected_contract_type = Request::input('contract_type', -1);


        $selected_hospitals = Hospital::where('id', '=', $selected_hospitals)->pluck("name", "id");
        if ($selected_physicians && $selected_agreements) {
            foreach ($selected_hospitals as $hospital_id => $hospital_name) {

                $physician_data = array();

                $physicians = Physician::select('physicians.id as id', DB::raw(("concat(physicians.last_name, ', ', physicians.first_name) as name")))
                    // ->whereIn("physicians.id", [$selected_physicians])
                    ->whereIn("physicians.id", $selected_physicians)
                    ->distinct()
                    ->get();

                foreach ($physicians as $physician) {

                    $contracts_with_approver = Contract::select('contracts.id as contract_id')
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                        ->where('agreements.archived', '=', false)
                        // ->where('agreements.approval_process', '=', '1')
                        ->where('contracts.physician_id', '=', $physician->id)
                        ->whereIn("agreements.id", $selected_agreements)
                        ->where('agreements.hospital_id', '=', $hospital_id);

                    if ($selected_contract_type != -1) {
                        $contracts_with_approver = $contracts_with_approver->where('contracts.contract_type_id ', '=', $selected_contract_type);
                    }
                    $contracts_with_approver = $contracts_with_approver->distinct()->get();

                    // Avg Time Month End to Physician Submtting E-signature new
                    $avg_time_month_end_to_physician = PhysicianLog::where('physician_logs.physician_id', $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_time_month_end_to_physician = $avg_time_month_end_to_physician->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_time_month_end_to_physician = $avg_time_month_end_to_physician->avg('physician_logs.time_to_approve_by_physician');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_physician');

                    // Level 1
                    $avg_with_approver_level1 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level1 = $avg_with_approver_level1->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level1 = $avg_with_approver_level1->avg('physician_logs.time_to_approve_by_level_1');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_1');

                    // Level 2
                    $avg_with_approver_level2 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level2 = $avg_with_approver_level2->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level2 = $avg_with_approver_level2->avg('physician_logs.time_to_approve_by_level_2');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_2');

                    // Level 3
                    $avg_with_approver_level3 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level3 = $avg_with_approver_level3->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level3 = $avg_with_approver_level3->avg('physician_logs.time_to_approve_by_level_3');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_3');

                    // Level 4
                    $avg_with_approver_level4 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level4 = $avg_with_approver_level4->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level4 = $avg_with_approver_level4->avg('physician_logs.time_to_approve_by_level_4');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_4');

                    // Level 5
                    $avg_with_approver_level5 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level5 = $avg_with_approver_level5->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level5 = $avg_with_approver_level5->avg('physician_logs.time_to_approve_by_level_5');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_5');

                    // Level 6
                    $avg_with_approver_level6 = PhysicianLog::where("physician_logs.physician_id", $physician->id)
                        ->whereNull('physician_logs.deleted_at')
                        ->whereIn("physician_logs.contract_id", $contracts_with_approver)
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id');
                    // ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                    if ($selected_contract_type != -1) {
                        $avg_with_approver_level6 = $avg_with_approver_level6->where('contracts.contract_type_id', $selected_contract_type);
                    }
                    $avg_with_approver_level6 = $avg_with_approver_level6->avg('physician_logs.time_to_approve_by_level_6');
                    // ->groupBy("physician_logs.physician_id")
                    // ->avg('physician_logs.time_to_approve_by_level_6');

                    // time to payment
                    $log_count = 0;
                    $total_days = 0;
                    foreach ($contracts_with_approver as $contract_id) {
                        $contract = Contract::where('id', '=', $contract_id->contract_id)->first();
                        $agreement_data = Agreement::getAgreementData($contract->agreement_id);
                        $payment_type_factory = new PaymentFrequencyFactoryClass();
                        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                        $period_dates = $res_pay_frequency['date_range_with_start_end_date'];


                        foreach ($period_dates as $dates_obj) {
                            // if( strtotime($dates_obj['start_date']) >= strtotime($startEndDatesForYear['year_start_date']->format('Y-m-d')) && strtotime($dates_obj['end_date']) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d')) ){
                            $query = PhysicianLog::select("physician_logs.*")
                                ->whereNull('physician_logs.deleted_at')
                                ->where('physician_logs.contract_id', '=', $contract->id)
                                ->whereBetween('physician_logs.date', [mysql_date($dates_obj['start_date']), mysql_date($dates_obj['end_date'])])
                                ->orderBy('physician_logs.date', 'asc');
                            // ->groupBy(DB::raw('MONTH(physician_logs.date)'));
                            $logs = $query->distinct()->first();
                            // log::info('$logs', array($logs));
                            if ($logs) {

                                $time_arr = [$logs->time_to_approve_by_physician, $logs->time_to_approve_by_level_1, $logs->time_to_approve_by_level_2, $logs->time_to_approve_by_level_3, $logs->time_to_approve_by_level_4, $logs->time_to_approve_by_level_5, $logs->time_to_approve_by_level_6, $logs->time_to_payment];
                                $days_sume = array_sum($time_arr);

                                $log_count = $log_count + 1;
                                $total_days += $days_sume;
                            }
                            // }
                        }
                    }
                    $avg_payment_time = ($log_count > 0 ? ($total_days / $log_count) : 0);

                    $physician_data[$physician->id]["physician"] = $physician->name;
                    $physician_data[$physician->id]["avg_submit_pay"] = number_format($avg_payment_time >= 0 ? $avg_payment_time : 0, 2);
                    $physician_data[$physician->id]["level0"] = number_format($avg_time_month_end_to_physician >= 0 ? $avg_time_month_end_to_physician : 0, 2);
                    $physician_data[$physician->id]["level1"] = number_format($avg_with_approver_level1 >= 0 ? $avg_with_approver_level1 : 0, 2);
                    $physician_data[$physician->id]["level2"] = number_format($avg_with_approver_level2 >= 0 ? $avg_with_approver_level2 : 0, 2);
                    $physician_data[$physician->id]["level3"] = number_format($avg_with_approver_level3 >= 0 ? $avg_with_approver_level3 : 0, 2);
                    $physician_data[$physician->id]["level4"] = number_format($avg_with_approver_level4 >= 0 ? $avg_with_approver_level4 : 0, 2);
                    $physician_data[$physician->id]["level5"] = number_format($avg_with_approver_level5 >= 0 ? $avg_with_approver_level5 : 0, 2);
                    $physician_data[$physician->id]["level6"] = number_format($avg_with_approver_level6 >= 0 ? $avg_with_approver_level6 : 0, 2);

                }
                $result[$hospital_id]['physician_data'] = $physician_data;
                $result[$hospital_id]['hospital_name'] = $hospital_name;
                $result[$hospital_id]['localtimeZone'] = $localtimeZone;
                // $result[$hospital_id]['period'] = "02/02/2022 - 02/02/2022"; // date("m-d-Y", strtotime($start_month)) . ' - ' . date("m-d-Y", strtotime($end_month));
            }
        }

        return $result;
    }

    public static function fetch_approver_report_data()
    {
        ini_set('max_execution_time', 6000);
        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        $result = array();
        $approver_data = array();
        $selected_approvers = Request::input('approvers');
        $selected_agreements = Request::input('agreements');
        $selected_hospitals = Request::input('hospital', 0);
        $start_month = Request::input('start_month');
        $end_month = Request::input('end_month');
        $selected_contract_type = Request::input('contract_type', -1);

        $selected_hospitals = Hospital::where('id', '=', $selected_hospitals)->pluck("name", "id");

        if ($selected_approvers && $selected_agreements) {
            foreach ($selected_hospitals as $hospital_id => $hospital_name) {

                $approver_data = array();

                $approvers = User::select('users.id as id', DB::raw(("concat(users.last_name, ', ', users.first_name) as name")))
                    ->whereIn("users.id", $selected_approvers)
                    ->distinct()
                    ->get();

                foreach ($approvers as $approver) {
                    $approver_contracts = DB::table('agreement_approval_managers_info')->select('contracts.id', 'contracts.agreement_id', 'agreement_approval_managers_info.level')
                        ->join("contracts", "contracts.agreement_id", "=", "agreement_approval_managers_info.agreement_id")
                        ->where('agreement_approval_managers_info.user_id', '=', $approver->id)
                        ->whereIn('contracts.agreement_id', $selected_agreements);

                    if ($selected_contract_type != -1) {
                        $approver_contracts = $approver_contracts->where('contracts.contract_type_id ', '=', $selected_contract_type);
                    }
                    $approver_contracts = $approver_contracts->distinct('contracts.id')->get();

                    $temp_contract_arr = array();
                    $temp_approver_time = array();
                    foreach ($approver_contracts as $contract) {

                        if ($contract->level <= 6) {
                            $level = $contract->level;
                            $previous_level = (int)$contract->level - 1;

                            // $avg_approver_time = PhysicianLog::select(DB::raw("AVG(CASE when log_approval.approval_managers_level = $level  then DATEDIFF(log_approval.`approval_date`,(select `approval_date` from log_approval where approval_managers_level = $previous_level AND log_id = `physician_logs`.id AND approval_status = 1 limit 1)) end) as average"),
                            // 	"physician_id", DB::raw("'1' as level"))
                            // 	->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                            // 	->where('physician_logs.date', '>=', $start_month)
                            // 	->where('physician_logs.date', '<=', $end_month)
                            // 	->where("physician_logs.contract_id", '=', $contract->id)
                            // 	->groupBy("log_approval.user_id")->get();

                            // Fetch time to approve data based on level
                            $column_to_fetch = "physician_logs.time_to_approve_by_level_" . $level;
                            $avg_with_approver_level = PhysicianLog::where("physician_logs.contract_id", $contract->id)
                                ->whereNull('physician_logs.deleted_at')
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id');
                            if ($selected_contract_type != -1) {
                                $avg_with_approver_level = $avg_with_approver_level->where('contracts.contract_type_id', $selected_contract_type);
                            }
                            $avg_with_approver_level = $avg_with_approver_level->avg($column_to_fetch);

                            if ($avg_with_approver_level) {
                                array_push($temp_approver_time, $avg_with_approver_level);
                                if (!in_array($contract->id, $temp_contract_arr)) {
                                    array_push($temp_contract_arr, $contract->id);
                                }
                            }

                            // if($avg_approver_time){
                            // 	foreach($avg_approver_time as $avg_approver_time){
                            // 		array_push($temp_approver_time, $avg_approver_time->average);
                            // 		if($avg_approver_time->average){

                            // 			if(!in_array($contract->id, $temp_contract_arr)){
                            // 				array_push($temp_contract_arr, $contract->id);
                            // 			}
                            // 		}
                            // 	}

                            // 	// if(!in_array($contract->id, $temp_contract_arr)){
                            // 	// 	array_push($temp_contract_arr, $contract->id);
                            // 	// }
                            // }
                        }
                    }

                    $total_avg_time = 0;
                    if (count($temp_approver_time) > 0) {
                        $total_avg_time = array_sum($temp_approver_time);
                    }

                    $total_contracts_approving = 0;
                    if (count($temp_contract_arr) > 0) {
                        $total_contracts_approving = count($temp_contract_arr);
                    }

                    $total_approved_logs = LogApproval::select('log_approval.*')
                        ->join('physician_logs', 'physician_logs.id', '=', 'log_approval.log_id')
                        // ->where('physician_logs.date', '>=', $start_month)
                        // ->where('physician_logs.date', '<=', $end_month)
                        ->where('log_approval.user_id', '=', $approver->id)->distinct('log_id')->count();

                    $total_rejected_logs = LogApproval::select('log_approval.*')
                        ->join('physician_logs', 'physician_logs.id', '=', 'log_approval.log_id')
                        // ->where('physician_logs.date', '>=', $start_month)
                        // ->where('physician_logs.date', '<=', $end_month)
                        ->where('log_approval.user_id', '=', $approver->id)->where('approval_status', '=', 0)->distinct('log_id')->count();

                    $rejection_rate = 0;
                    if ($total_approved_logs > 0 && $total_rejected_logs > 0) {
                        $rejection_rate = number_format(($total_rejected_logs / $total_approved_logs) * 100, 2);
                    }

                    $approver_data[$approver->id]['approver'] = $approver->name;
                    $approver_data[$approver->id]["avg_approving_time"] = number_format(($total_avg_time), 2); // ? $avg_submit_pay->average: 0;
                    $approver_data[$approver->id]['rejection_rate'] = $rejection_rate;
                    $approver_data[$approver->id]['total_contracts_approving'] = $total_contracts_approving;
                }
                $result[$hospital_id]['approver_data'] = $approver_data;
                $result[$hospital_id]['hospital_name'] = $hospital_name;
                $result[$hospital_id]['localtimeZone'] = $localtimeZone;
                // $result[$hospital_id]['period'] = date("m-d-Y", strtotime($start_month)) . ' - ' . date("m-d-Y", strtotime($end_month));
            }
        } else {

        }

        return $result;
    }

    public static function getTimePeriodByAgreements($facility, $agreements)
    {
        $agreement = preg_split("/[,]/", $agreements);
        $now = new DateTime('now');
        $results = array();

        $dates = self::datesForPerformanceDashboard($facility, $agreement);
        $date['dates'] = ["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        $time_period = PhysicianLog::datesOptions($date['dates']);

        return $time_period;
    }

    public static function datesForPerformanceDashboard($hospital_id, $agreement_id)
    {
        $dates = PhysicianLog::select(
            DB::raw("MIN(physician_logs.date) as temp_start_date"),
            DB::raw("MAX(physician_logs.date) as temp_end_date"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id');
        if ($hospital_id != 0) {
            $dates = $dates->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $dates = $dates->whereIN('contracts.agreement_id', $agreement_id);
        }

        $dates = $dates->where('agreements.archived', '=', false)
            ->orderBy('contracts.physician_id')
            ->orderBy('physician_logs.practice_id')
            ->orderBy('physician_logs.date')
            ->orderBy('physician_logs.next_approver_user', "DESC")
            ->first();
        return $dates;
    }
}
