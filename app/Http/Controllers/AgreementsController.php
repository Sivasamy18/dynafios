<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Validations\AgreementValidation;
use App\Agreement;
use App\ContractName;
use App\PhysicianPracticeHistory;
use App\Hospital;
use App\Group;
use App\PhysicianType;
use App\Practice;
use App\User;
use App\ApprovalManagerType;
use App\ApprovalManagerInfo;
use App\AgreementInternalNote;
use App\OnCallSchedules;
use App\PhysicianLog;
use App\Amount_paid;
use App\Console\Commands\OnCallScheduleCommand;
use App\HospitalInvoice;
use App\Jobs\SendEmail;
use App\ContractRate;
use App\Action;
use App\Contract;
use App\ContractType;
use App\PaymentType;
use App\Physician;
use App\InvoiceNote;
use App\PhysicianDeviceToken;
use App\PaymentStatusDashboard;
use Barryvdh\DomPDF\Facade as PDF;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Jobs\UpdatePendingPaymentCount;
use App\Jobs\UpdatePaymentStatusDashboard;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use stdClass;
use function App\Start\is_hospital_owner;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\hospital_report_path;
use App\ActionCategories;
use App\Http\Controllers\Validations\EmailValidation;

class AgreementsController extends ResourceController
{
    protected $requireAuth = true;

    public function getShow($id)
    {
        $agreement = Agreement::findOrFail($id);
        if ($agreement->is_deleted == false) {
            $hospital = $agreement->hospital;

            if (!is_hospital_owner($hospital->id))
                App::abort(403);

            $data['hospital'] = $hospital;
            $data['agreement'] = $agreement;
            $data['expiring'] = $this->isAgreementExpiring($agreement);
            $data['expired'] = $this->isAgreementExpired($agreement);
            $data['archived'] = false;
            if ($agreement->archived) {
                $data['expired'] = false;
                $data['archived'] = true;
            }
            $data['remaining'] = $this->getRemainingDays($agreement);
            $agree_contract = $this->getContracts($agreement);
            $data['contracts'] = $agree_contract;
            $data['agreement_contracts'] = $agree_contract;
            $data['dates'] = Agreement::getAgreementData($agreement);
            foreach ($data['contracts'] as $contracts) {
                $data['contract_type_id'] = $contracts->contract_type_id;
                $data['payment_type_id'] = $contracts->payment_type_id;
                if ($contracts->contract_type_id == ContractType::ON_CALL && $contracts->payment_type_id == PaymentType::PER_DIEM) {
                    $data['contract_type_id'] = $contracts->contract_type_id;
                    $data['payment_type_id'] = $contracts->payment_type_id;
                    break;
                }
            }

            foreach ($data['agreement_contracts'] as $agreement_contract) {
                $contract_name_id = DB::table('contracts')
                    ->select('contract_name_id')
                    ->where('id', '=', $agreement_contract->id)
                    ->first();
                $practices = $agreement_contract->practices;
                foreach ($practices as $practice) {
                    $practice->physicians = array();
                    $current_date = mysql_date(date('Y-m-d'));
                    $physicians = PhysicianPracticeHistory::select(
                        DB::raw("physician_practice_history.*"),
                        DB::raw("contracts.id as contract_id"))

                        ->join('physician_contracts', function ($join) {
                            $join->on('physician_contracts.physician_id', '=', 'physician_practice_history.physician_id');
                            $join->on('physician_contracts.practice_id', '=', 'physician_practice_history.practice_id');

                        })
                        ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                        ->join('physician_practices', function ($join) {
                            $join->on('physician_practices.physician_id', '=', 'physician_practice_history.physician_id');
                            $join->on('physician_practices.practice_id', '=', 'physician_practice_history.practice_id');

                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where('contracts.contract_name_id', '=', $contract_name_id->contract_name_id)
                        ->where('contracts.contract_type_id', '=', $agreement_contract->contract_type_id)
                        ->where('contracts.payment_type_id', '=', $agreement_contract->payment_type_id)
                        ->where('physician_practice_history.end_date', '>', $current_date)
                        ->where('physician_practice_history.practice_id', '=', $practice->id)
                        ->whereRaw('physician_practice_history.start_date <= now()')
                        ->whereRaw('physician_practice_history.end_date >= now()')
                        ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                        ->whereNull('contracts.deleted_at')
                        ->whereNull('physician_contracts.deleted_at')
                        ->where('physician_practices.practice_id', '=', $practice->id)
                        ->where('contracts.id', '=', $agreement_contract->id)
                        ->whereNull('physician_practices.deleted_at')
                        ->orderBy('physician_practice_history.first_name')
                        ->orderBy('physician_practice_history.last_name')
                        ->get();

                    foreach ($physicians as $index => $physician) {
                        $physician_data = new StdClass();
                        $physician_data->id = $physician->physician_id;
                        $physician_data->contract_id = $physician->contract_id;
                        $physician_data->name = "{$physician->last_name}, {$physician->first_name}";
                        $physician_data->first = $index == 0;

                        $practice->physicians[] = $physician_data;
                    }
                }
            }
            $data['table'] = View::make('agreements/_contracts')->with($data)->render();
            if ($data['remaining'] < 0) {
                $data['remaining'] = 0;
            }
            return View::make('agreements/show')->with($data);
        } else {
            $hospital = $agreement->hospital;
            return Redirect::route('hospitals.agreements', $hospital->id);
        }

    }

    private function isAgreementExpiring($agreement)
    {
        return $this->getRemainingDays($agreement) <= 15;
    }

    private function getRemainingDays($agreement)
    {
        return days('now', $agreement->end_date);
    }

    private function isAgreementExpired($agreement)
    {
        return $this->getRemainingDays($agreement) < 0;
    }

    private function getContracts($agreement)
    {
        $data = Agreement::getContracts($agreement);

        return $data;
    }

    public function getShowOnCall($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $data['hospital'] = $hospital;
        $data['agreement'] = $agreement;
        $data['expiring'] = $this->isAgreementExpiring($agreement);
        $data['expired'] = $this->isAgreementExpired($agreement);
        if ($agreement->archived) {
            $data['expired'] = false;
        }
        $data['remaining'] = $this->getRemainingDays($agreement);
        $contracts = $this->getContracts($agreement);
        foreach ($contracts as $key => $contract) {
            if ($contract->payment_type_id != PaymentType::PER_DIEM) {
                unset($contracts[$key]);
            }
        }
        $data['contracts'] = $contracts;
        $data['dates'] = Agreement::getAgreementData($agreement);
        $data['current_month'] = $data['dates']->current_month;
        foreach ($data['dates']->dates as $date) {
            $date_array[] = $date;
        }
        $start_date_array[] = $data['dates']->start_dates;
        $data['date_array'] = $date_array;
        $data['start_date_array'] = $start_date_array;
        $date = $data['dates']->start_date;
        $date_parse = date_parse_from_format("m/d/Y", $date);
        $month = $date_parse["month"];
        $year = $date_parse["year"];
        $start_date = "01-" . $month . "-" . $year;
        $start_time = strtotime($start_date);
        $end_time = strtotime("+1 month", $start_time);

        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $list[] = date('m/d/Y', $i);
        }
        $data['pluck'] = $list;
        if ($data['remaining'] < 0)
            $data['remaining'] = 0;
        return View::make('agreements/on_call')->with($data);
    }

    public function getShowOnCallEntry($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $data['agreement'] = $agreement;
        $data['hospital'] = $hospital;
        $activities = DB::table('actions')
            ->select('id', 'name')
            ->whereIn("action_type_id", [1, 2])
            ->where('payment_type_id', '=', PaymentType::PER_DIEM)
            ->orderBy("action_type_id", "asc")
            ->orderBy("name", "asc")
            ->get();

        $contracts = $this->getContracts($agreement);
        $physicians_array = array();
        foreach ($contracts as $contract) {
            foreach ($contract->practices as $practice) {
                foreach ($practice->physicians as $physicians) {
                    $physicians_array[] = [
                        "id" => $physicians->id,
                        "name" => $physicians->name
                    ];
                }
            }
        }
        $data['activities'] = $activities;
        $data['physicians'] = $physicians_array;

        $agreement_data = Agreement::getAgreementData($id);
        $agreement_month = $agreement_data->months[$agreement_data->current_month];
        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"
        $prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $recent_logs = PhysicianLog::select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->orderBy("date", "desc")
            ->get();
        $results = array();

        foreach ($recent_logs as $recent_log) {
            if ($recent_log->action_id != 0) {
                $action = Action::findOrFail($recent_log->action_id);
                $physician = DB::table('physicians')
                    ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                    ->where('id', '=', $recent_log->physician_id)->first();
                $results[] = [
                    "id" => $recent_log->id,
                    "physician_name" => $physician->full_name,
                    "action" => $action->name,
                    "date" => format_date($recent_log->date),
                    "duration" => $recent_log->duration,
                    "hospital_id" => $hospital->id,
                    "contract_id" => $recent_log->contract_id,
                    "created" => format_date($recent_log->created_at, "m/d/Y h:i A")
                ];
            }
        }
        $data['recent_logs'] = $results;
        return View::make('agreements/on_call_entry')->with($data);
    }

    public function deleteOnCallEntry($id, $log_id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        try {
            PhysicianLog::where('id', "=", $log_id)->delete();
            return "SUCCESS";
        } catch (Exception $e) {
            return "ERROR";
        }
    }

    public function getPreLogDate($id, $physician_id)
    {
        $recent_dates = PhysicianLog::select(DB::raw('distinct(date)'))
            ->whereRaw("physician_logs.date >= date(now() - interval 90 day)")
            ->where("physician_id", "=", $physician_id)
            ->orderBy("date", "desc")
            ->get();
        $date = array();
        foreach ($recent_dates as $recent_date) {
            $date[] = $recent_date->date;
        }
        return $date;
    }

    public function approveOnCallLogs($id)
    {
        $log_ids = Request::all();
        if (array_key_exists('submit', $log_ids)) {

            $action_id = $log_ids['action'];
            $duration = 0;
            if ($action_id != "") {
                $action_duration = DB::table('action_contract')
                    ->select('hours')
                    ->where('action_id', '=', $action_id)
                    ->first();
                $duration = $action_duration->hours;
            }
            $log_details = $log_ids['log_details'];
            $physician_id = $log_ids['physician_name'];
            $physician = Physician::findOrFail($physician_id);
            $contract_id = $log_ids['agreement_id'];
            $selected_dates = $log_ids['selected_dates'];
            $selected_dates_array = explode(',', $selected_dates);
            foreach ($selected_dates_array as $selected_date) {
                $user = PhysicianLog::where('physician_id', '=', $physician_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->where('contract_id', '=', $contract_id)
                    ->first();
                if (count($user) > 0) {
                    PhysicianLog::where('physician_id', '=', $physician_id)
                        ->where('date', '=', mysql_date($selected_date))
                        ->where('contract_id', '=', $contract_id)
                        ->update(array(
                            'duration' => $duration,
                            'action_id' => $action_id,
                            'details' => $log_details
                        ));
                } else {
                    $physician_logs = new PhysicianLog();
                    $physician_logs->physician_id = $physician_id;
                    $practice_info = DB::table('physician_practice_history')
                        ->where('physician_id', '=', $physician_id)
                        ->where('start_date', '<', mysql_date($selected_date))
                        ->where('end_date', '>=', mysql_date($selected_date))
                        ->first();
                    if (count($practice_info) > 0) {
                        $physician_logs->practice_id = $practice_info->practice_id;
                    } else {
                        $contract = Contract::findOrFail($contract_id);
                        $physician_logs->practice_id = $contract->practice_id;
                    }
                    $physician_logs->contract_id = $contract_id;
                    $physician_logs->action_id = $action_id;
                    $physician_logs->date = mysql_date($selected_date);
                    $physician_logs->duration = $duration;
                    $physician_logs->signature = 0;
                    $physician_logs->details = $log_details;
                    $physician_logs->approval_date = "0000-00-00";
                    $physician_logs->save();
                }
            }
            return Redirect::back()
                ->with(['success' => Lang::get('agreements.logs_insert_success')
                ])
                ->withInput();
        }
        if (array_key_exists('approve_logs', $log_ids)) {
            if (array_key_exists('log_ids', $log_ids)) {
                foreach ($log_ids['log_ids'] as $log_id) {
                    $fetch_physician_id = PhysicianLog::select('physician_id')
                        ->where("id", "=", $log_id)
                        ->first();

                    if ($fetch_physician_id->physician_id != '') {
                        $fetch_signature_ids = DB::table('signature')->select('signature_id')
                            ->where("physician_id", "=", $fetch_physician_id->physician_id)
                            ->get();


                        if (count($fetch_signature_ids) > 0) {

                            foreach ($fetch_signature_ids as $fetch_signature_id) {
                                $result = DB::table('physician_logs')
                                    ->where('id', $log_id)
                                    ->update(array('signature' => $fetch_signature_id->signature_id, 'approval_date' => date("Y-m-d")));
                            }
                        } else {
                            $result = DB::table('physician_logs')
                                ->where('id', $log_id)
                                ->update(array('approval_date' => date("Y-m-d")));
                        }
                    }
                }
                return Redirect::back()
                    ->with(['success' => Lang::get('agreements.noPendingLogsForApproval')
                    ])
                    ->withInput();
            } else {
                return Redirect::back()
                    ->with(['error' => Lang::get('agreements.noLogs')
                    ])
                    ->withInput();
            }
        }
    }

    public function getDataOnCall($id, $date_index)
    {
        $date_index = $date_index;
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $data['hospital'] = $hospital;
        $data['agreement'] = $agreement;
        $data['expiring'] = $this->isAgreementExpiring($agreement);
        $data['expired'] = $this->isAgreementExpired($agreement);
        if ($agreement->archived) {
            $data['expired'] = false;
        }
        $data['remaining'] = $this->getRemainingDays($agreement);
        $data['contracts'] = $this->getContracts($agreement);
        $physicians_array = array();
        $physician_id_array = array();
        foreach ($data['contracts'] as $contractss) {
            foreach ($contractss->practices as $practice) {
                foreach ($practice->physicians as $physicians) {

                    if (!(in_array($physicians->id, $physician_id_array))) {
                        array_push($physician_id_array, $physicians->id);
                        $physicians_array[] = [
                            "id" => $physicians->id,
                            "name" => $physicians->name
                        ];
                    }

                }
            }
        }
        $data['all_physicians'] = $physicians_array;
        $data['dates'] = Agreement::getAgreementData($agreement);
        $selected_date = explode(": ", $data['dates']->start_dates[$date_index]);
        $date = mysql_date($selected_date[1]);
        $data['fetch_oncall_data'] = DB::table('on_call_schedule')->select('practice_id', 'physician_id',
            'physician_type', 'date')
            ->where("on_call_schedule.agreement_id", "=", $id)
            ->get();
        $start_date_obj = new DateTime($selected_date[1]);
        $start_date_settime = $start_date_obj->setTime(00, 00, 00);
        $start_date_formatted = $start_date_settime->format('Y-m-d H:i:s');
        $start_time = strtotime($start_date_formatted);
        $end_time = strtotime("+1 month", $start_time);
        $agreement_end_date_obj = new DateTime($data['dates']->end_date);
        $agreement_end_date_settime = $agreement_end_date_obj->setTime(23, 59, 59);
        $agreement_end_date_formatted = $agreement_end_date_settime->format('Y-m-d H:i:s');
        $agreement_end_date = strtotime($agreement_end_date_formatted);

        for ($i = $start_time; $i < $end_time && $i <= $agreement_end_date; $i += 86400) {
            $day = strftime("%a", strtotime(date('m/d/Y', $i)));
            $list[] = $day . " " . date('m/d/Y', $i);
        }
        $list = array_unique($list);
        $data['start_date_list'] = $list;
        $data['table'] = View::make('agreements/dynamic_dates')->with($data)->render();
        return $data;
    }

    public function postSaveOnCall($id)
    {
        $agreement = Agreement::findOrFail($id);
        $agreement_id = $id;
        $data['contracts'] = $this->getContracts($agreement);
        foreach ($data['contracts'] as $cn) {
            $contract_name = $cn->name;
        }
        $data['dates'] = Agreement::getAgreementData($agreement);
        $pm_physicians = Request::all();
        //for copying previous month schedules to this month
        if (array_key_exists('renew', $pm_physicians)) {
            $selected_date = $pm_physicians['select_date'];
            $fetch_oncall_data_prev_month = $this->previous_month_schedules($selected_date, $agreement_id);
            //if previous month schedule is not empty
            if (count($fetch_oncall_data_prev_month) > 0) {
                $agreement_data = Agreement::getAgreementData($agreement_id);
                $agreement_end_date = $agreement_data->end_date;
                $agreement_data_months["months"] = $agreement_data->dates;
                $current_month = explode(": ", $agreement_data_months["months"][$pm_physicians['select_date']]);
                $current_month_date = explode("-", $current_month[1]);
                $current_month_start_date = mysql_date($current_month_date[0]);
                $current_month_end_date = mysql_date($current_month_date[1]);
                $this->copy_schedule_for_month($current_month_start_date, $current_month_end_date, $fetch_oncall_data_prev_month, $agreement_id, $agreement_end_date);

                return Redirect::back()
                    ->with(['success' => Lang::get('agreements.schedule_insert_success'),
                        'date_select_index' => $pm_physicians['select_date']
                    ])
                    ->withInput();

            } else {
                return Redirect::back()
                    ->with(['error' => Lang::get('agreements.schedule_failure'),
                        'date_select_index' => $pm_physicians['select_date']
                    ])
                    ->withInput();
            }

        } //for copying previous month schedules to all months till end of agreement date
        elseif (array_key_exists('renew_full', $pm_physicians)) {
            $agreement_data = Agreement::getAgreementData($agreement_id);
            $agreement_data_months["months"] = $agreement_data->dates;
            $agreement_end_date = $agreement_data->end_date;
            $count_months = count($agreement_data_months["months"]);
            $selected_date = $pm_physicians['select_date'];
            $fetch_oncall_data_prev_month = $this->previous_month_schedules($selected_date, $agreement_id);
            //if previous month schedule is not empty
            if (count($fetch_oncall_data_prev_month) > 0) {
                for ($i = $pm_physicians['select_date']; $i <= $count_months; $i++) {
                    $agreement_data = Agreement::getAgreementData($agreement_id);
                    $agreement_data_months["months"] = $agreement_data->dates;
                    $current_month = explode(": ", $agreement_data_months["months"][$i]);
                    $current_month_date = explode("-", $current_month[1]);
                    $current_month_start_date = mysql_date($current_month_date[0]);
                    $current_month_end_date = mysql_date($current_month_date[1]);
                    $this->copy_schedule_for_month($current_month_start_date, $current_month_end_date, $fetch_oncall_data_prev_month, $agreement_id, $agreement_end_date);
                }
                return Redirect::back()
                    ->with(['success' => Lang::get('agreements.schedule_insert_success'),
                        'date_select_index' => $pm_physicians['select_date']
                    ])
                    ->withInput();
            } else {
                return Redirect::back()
                    ->with(['error' => Lang::get('agreements.schedule_failure'),
                        'date_select_index' => $pm_physicians['select_date']
                    ])
                    ->withInput();
            }
        } else {
            $last_date = $pm_physicians['date_count'] - 1;
            $fetch_oncall_data = DB::table('on_call_schedule')->select('id', 'physician_type', 'date')
                ->where("on_call_schedule.agreement_id", "=", $agreement_id)
                ->whereBetween('on_call_schedule.date',
                    array(date("Y-m-d", strtotime($pm_physicians['date_div_0'])),
                        date("Y-m-d", strtotime($pm_physicians['date_div_' . $last_date]))))
                ->get();

            foreach ($fetch_oncall_data as $key => $db_date) {
                $temp_day = explode('-', $db_date->date);
                $db_actual_date = $temp_day[2] - 1;
                if ($db_date->physician_type == PhysicianLog::SHIFT_AM) {
                    if ($pm_physicians['am_phisicians_' . $db_actual_date] == 0) {
                        DB::table('on_call_schedule')
                            ->where('on_call_schedule.id', '=', $db_date->id)
                            ->where('on_call_schedule.agreement_id', '=', $agreement_id)
                            ->whereBetween('on_call_schedule.date', array(date("Y-m-d", strtotime($pm_physicians['date_div_0'])), date("Y-m-d", strtotime($pm_physicians['date_div_' . $last_date]))))
                            ->delete();
                    }
                } else {
                    if ($pm_physicians['pm_phisicians_' . $db_actual_date] == 0) {
                        DB::table('on_call_schedule')
                            ->where('on_call_schedule.id', '=', $db_date->id)
                            ->where('on_call_schedule.agreement_id', '=', $agreement_id)
                            ->whereBetween('on_call_schedule.date', array(date("Y-m-d", strtotime($pm_physicians['date_div_0'])), date("Y-m-d", strtotime($pm_physicians['date_div_' . $last_date]))))
                            ->delete();
                    }
                }
            }

            $remove = array(0);
            $phy_arr = array();
            $result = array_diff($pm_physicians, $remove);
            $start_date_array = $data['dates']->start_dates[$result['select_date']];
            $temp_start_date = explode(':', $start_date_array);
            $date = $temp_start_date[1];
            $end_date_array = $data['dates']->end_dates[$result['select_date']];
            $temp_end_date = explode(':', $end_date_array);
            $end_date = $temp_end_date[1];

            $report_start_date = $date;
            $report_end_date = $end_date;
            $date_arr = explode('/', $date);
            $month = $date_arr[0];
            $day = $date_arr[1];
            $year = $date_arr[2];

            $practice_id = $result['practice_id'];
            /*
             * "am_phisicians_" and "pm_phisicians_"
             * are the name of resp select boxes */
            for ($i = 0; $i < $result['date_count']; $i++) {
                $j = $i;
                $k = $j + 1;
                if (array_key_exists('am_phisicians_' . $i, $result)) {
                    $phy_arr[] = [
                        "physician_id" => $result['am_phisicians_' . $i],
                        "physician_type" => 1,
                        "date" => $result['date_div_' . $i]
                    ];
                }
                if (array_key_exists('pm_phisicians_' . $i, $result)) {
                    $phy_arr[] = [
                        "physician_id" => $result['pm_phisicians_' . $i],
                        "physician_type" => 2,
                        "date" => $result['date_div_' . $i]
                    ];
                }
            }


            if (array_key_exists('save', $pm_physicians)) {
                if (count($phy_arr) > 0) {
                    foreach ($phy_arr as $array) {
                        $check_data = DB::table('on_call_schedule')->select('practice_id')
                            ->where("agreement_id", "=", $agreement_id)
                            ->where("physician_type", "=", $array['physician_type'])
                            ->where("date", "=", mysql_date($array['date']))
                            ->count();

                        $contractID = 0;
                        $contractData = DB::table('contracts')
                            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                            ->where('agreement_id', '=', $agreement_id)
                            ->where('physician_contracts.physician_id', '=', $array['physician_id'])
                            ->value('contracts.id');

                        if ($contractData != "") {
                            $contractID = $contractData;
                        } else {
                            return Redirect::back()
                                ->with(['error' => "We found data mismatch in records. Please contact system admin.",
                                    'date_select_index' => $result['select_date']
                                ])
                                ->withInput();
                        }

                        if ($check_data == 0) {

                            $data['contractData'] = $contractData;
                            $oncall = new OnCallSchedules();
                            $oncall->agreement_id = $agreement_id;
                            $oncall->contract_id = $contractID;
                            $oncall->practice_id = $practice_id;
                            $oncall->physician_id = $array['physician_id'];
                            $oncall->physician_type = $array['physician_type'];
                            $oncall->date = mysql_date($array['date']);
                            $oncall->save();
                        } else {
                            $update_data = DB::table('on_call_schedule')
                                ->where("agreement_id", "=", $agreement_id)
                                ->where("date", "=", mysql_date($array['date']))
                                ->where("physician_type", "=", $array['physician_type'])
                                ->update(array('physician_id' => $array['physician_id'],
                                    'contract_id' => $contractID, 'practice_id' => $practice_id));
                        }
                    }
                    if ($check_data == 0) {
                        if ($oncall->save()) {
                            return Redirect::back()
                                ->with(['success' => Lang::get('agreements.schedule_insert_success'),
                                    'date_select_index' => $result['select_date']
                                ])
                                ->withInput();
                        }
                    } else {
                        return Redirect::back()
                            ->with(['success' => Lang::get('agreements.schedule_update_success'),
                                'date_select_index' => $result['select_date']
                            ])
                            ->withInput();
                    }
                }
                return Redirect::back()
                    ->with(['error' => Lang::get('agreements.select_one'),
                        'date_select_index' => $result['select_date']
                    ])
                    ->withInput();
            }


            if (array_key_exists('export', $pm_physicians)) {

                $hospital = Hospital::findOrFail($agreement->hospital_id);

                if (!is_hospital_owner($hospital->id))
                    App::abort(403);
                Artisan::call('reports:oncallschedule', [
                    'hospital' => $hospital->id,
                    'report_start_date' => $report_start_date,
                    'report_end_date' => $report_end_date,
                    'contract_name' => $contract_name,
                    'agreement_id' => $agreement_id
                ]);
                if (!OnCallScheduleCommand::$success) {
                    return Redirect::back()->with([
                        'error' => Lang::get(OnCallScheduleCommand::$message)
                    ]);
                }
                return Redirect::back()->with([
                    'success' => Lang::get(OnCallScheduleCommand::$message),
                    'report_id' => OnCallScheduleCommand::$report_id,
                    'report_filename' => OnCallScheduleCommand::$report_filename,
                    'hospital_id' => $hospital->id,
                    'date_select_index' => $result['select_date']
                ]);
            }
        }

    }

    public function previous_month_schedules($selected_date, $agreement_id)
    {
        $agreement_data = Agreement::getAgreementData($agreement_id);

        // Append month information to the data array.
        $agreement_data_months["months"] = $agreement_data->dates;
        $prev_month_selection = $selected_date - 1;
        $prev_month = explode(": ", $agreement_data_months["months"][$prev_month_selection]);
        $prev_month_date = explode("-", $prev_month[1]);
        $prev_month_start_date = mysql_date($prev_month_date[0]);
        $prev_month_end_date = mysql_date($prev_month_date[1]);

        //fetch data from db of previous month
        $fetch_oncall_data_prev_month = DB::table('on_call_schedule')->select('id', 'practice_id', 'physician_id', 'physician_type', 'date', 'contract_id')
            ->where("on_call_schedule.agreement_id", "=", $agreement_id)
            ->whereBetween('on_call_schedule.date',
                array($prev_month_start_date, $prev_month_end_date))
            ->get();
        return $fetch_oncall_data_prev_month;
    }

    public function copy_schedule_for_month($start_date, $end_date, $previous_schedule, $agreement_id, $agreement_end_date)
    {
        $agreement_end_date = date("Y-m-d", strtotime($agreement_end_date));
        $dynamic_date = $start_date;
        while ($dynamic_date <= $end_date) {
            $dynamic_date_formatted = date_parse_from_format("Y-m-d", $dynamic_date);
            $dynamic_date_day = $dynamic_date_formatted['day'];
            foreach ($previous_schedule as $oncall_data) {
                $prev_scheduled_date = date_parse_from_format("Y-m-d", $oncall_data->date);
                $prev_scheduled_date_day = $prev_scheduled_date['day'];
                if ($dynamic_date <= $agreement_end_date) {
                    if ($prev_scheduled_date_day == $dynamic_date_day) {
                        $check_data = DB::table('on_call_schedule')->select('practice_id')
                            ->where("agreement_id", "=", $agreement_id)
                            ->where("physician_type", "=", $oncall_data->physician_type)
                            ->where("date", "=", $dynamic_date)
                            ->count();
                        if ($check_data == 0) {

                            $oncall = new OnCallSchedules();
                            $oncall->agreement_id = $agreement_id;
                            $oncall->contract_id = $oncall_data->contract_id;
                            $oncall->physician_id = $oncall_data->physician_id;
                            $oncall->physician_type = $oncall_data->physician_type;
                            $oncall->date = $dynamic_date;
                            $oncall->save();
                        } else {
                            $update_data = DB::table('on_call_schedule')
                                ->where("agreement_id", "=", $agreement_id)
                                ->where("date", "=", $dynamic_date)
                                ->where("physician_type", "=", $oncall_data->physician_type)
                                ->update(array('physician_id' => $oncall_data->physician_id,
                                    'contract_id' => $oncall_data->contract_id));
                        }
                    }
                }
            }
            $last_month_first_date = with(new DateTime($dynamic_date))->setTime(0, 0, 0)->modify('+1 day');
            $dynamic_date = mysql_date($last_month_first_date->format('Y-m-d'));
        }
    }

    /***
     * This(Old) function which converted for validating data and then pass the validated data to the actual submit payment function.
     */
    public function addPayment()
    {
   
        $data = Request::input('vals');
        $contract_ids = Request::input('contract_ids');
        $practice_ids = Request::input('practice_ids');
        $prev_amounts = Request::input('prev_values');
        $start_date = date("Y-m-d", strtotime(Request::input('start_date')));
        $end_date = date("Y-m-d", strtotime(Request::input('end_date')));
        $selected_date = Request::input('selected_date');
        $hospital_id = Request::input('hospital_id');
        $finalPayment = Request::input('finalPayment');
        $print_all_invoice_flag = Request::input('print_all_invoice_flag');


        $timestamp = Request::input("timestamp");
        $timeZone = Request::input("timeZone");
        $invoice_result = [];
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


        $error_obj = [];
        $hospital_invoice_error_arr = [];
        $physician_invoice_error_arr = [];
        $contract_invoice_error_arr = [];
        $practice_invoice_error_arr = [];

        $result_hospital = Hospital::findOrFail($hospital_id);

        if ($result_hospital->invoice_type == 1 && $print_all_invoice_flag == "true") {
            return Response::json([
                "status" => true,
                "data" => [],
                "errorCount" => 0,
                "invoice_type" => $result_hospital->invoice_type
            ]);
        }


        $payment_data_arr = [];
        $custome_invoice_error_count = 0;
        if ($contract_ids != null) {
            if (count($contract_ids) > 0) {

                foreach ($contract_ids as $index => $contract_id) {
                    $check_final_payment = Amount_paid::where('contract_id', '=', $contract_id)
                        ->where('practice_id', '=', $practice_ids[$index])
                        ->where('start_date', '=', $start_date)
                        ->where('end_date', '=', $end_date)
                        ->where('final_payment', '=', 1)->get();

                    if(count($check_final_payment) == 0){
                        $temp_arr = [];

                        $temp_arr['contractId'] = [$contract_id];
                        $temp_arr['data'] = [$data[$index]];
                        $temp_arr['practiceId'] = [$practice_ids[$index]];
                        $temp_arr['startDate'] = $start_date;
                        $temp_arr['endDate'] = $end_date;
                        $temp_arr['selectedDate'] = $selected_date;
                        $temp_arr['prev_amount'] = [];
                        $temp_arr['finalPayment'] = [$finalPayment[$index]];

                        if (!array_key_exists($contract_id, $payment_data_arr)) {
                            $payment_data_arr[$contract_id] = $temp_arr;
                        }
                    }
                }
            }

            if ($prev_amounts != null) {
                if (count($prev_amounts) > 0) {
                    foreach ($prev_amounts as $prev_amount) {
                        $temp_arr_prev = [];
                        $amount_paid_obj = Amount_paid::where('id', '=', $prev_amount['id'])->where('final_payment', '=', 0)->first();
                        if ($amount_paid_obj) {
                            if (array_key_exists($amount_paid_obj->contract_id, $payment_data_arr)) {
                                $payment_data_arr[$amount_paid_obj->contract_id]['prev_amount'][] = $prev_amount;
                            }
                        }
                    }
                }
            }

            if (count($payment_data_arr) > 0) {
                foreach ($payment_data_arr as $paymentObj) {
             
                    extract($paymentObj);
                    $result = $this->addPaymentActual($data, $contractId, $practiceId, $startDate, $endDate, $selectedDate, $prev_amount, $finalPayment, $localtimeZone, $result_hospital, $print_all_invoice_flag, $invoice_result);
             
                    if ($result != 0) {
                        $invoice_result = $result;
                    }
                }
                
                if(count($invoice_result) == 0){
                    $print_all_invoice_flag = "false";
                }

                $invoice_result['print_all_invoice_flag'] = $print_all_invoice_flag;
                if ($print_all_invoice_flag == "true") {
                    if (count($invoice_result) > 0) {
                        $last_invoice_no = $invoice_result["data"][0]["agreement_data"]["invoice_no"];
                        $start_date = mysql_date($invoice_result['start_date']);
                        $report_path = hospital_report_path($invoice_result["hospital"]);
                        $custom_date_with_random = date('mdYhis') + rand();

                        $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                        $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                        $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                        $report_filename = "Invoices_" . $invoice_result["hospital"]->name . "_" . $time_stamp_zone . "_" . $custom_date_with_random . ".pdf";
                        if (!File::exists($report_path)) {
                            File::makeDirectory($report_path, 0777, true, true);
                        };

                        try {
                            $customPaper = array(0, 0, 1683.78, 595.28);
                            $pdf = PDF::loadView('agreements/invoice_pdf', $invoice_result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                            $hospital_invoice = new HospitalInvoice;
                            $hospital_invoice->hospital_id = $invoice_result["hospital"]->id;
                            $hospital_invoice->filename = $report_filename;
                            $hospital_invoice->contracttype_id = 0;
                            $hospital_invoice->period = mysql_date(date("m/d/Y"));
                            $hospital_invoice->last_invoice_no = $last_invoice_no;
                            $hospital_invoice->save();

                            $report_id = $hospital_invoice->id;
                            $report_path = hospital_report_path($invoice_result["hospital"]);
                            $data['month'] = date("F", strtotime($start_date));
                            $data['year'] = date("Y", strtotime($start_date));
                            $data['name'] = "DYNAFIOS";
                            $data['email'] = $invoice_result["recipient"];
                            $data['file'] = $report_path . "/" . $report_filename;
                            SendEmail::dispatch($data);

                            foreach ($last_invoice_no = $invoice_result["data"][0]["practices"] as $practice_id => $practice) {
                                foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                                    $data_physician['month'] = date("F", strtotime($start_date));
                                    $data_physician['year'] = date("Y", strtotime($start_date));
                                    $data_physician['name'] = "DYNAFIOS";
                                    $data_physician['email'] = $contract_data['physician_email'];
                                    $data_physician['file'] = $report_path . "/" . $report_filename;
                                    $data_physician['is_final_pmt'] = $contract_data['is_final_payment'];
                                    $data_physician['amount_to_be_paid'] = $contract_data['amount_paid'];
                                    $data_physician['hospital'] = $invoice_result["hospital"]['name'];
                                    $data_physician['physician_name'] = $contract_data['physician_name'];
                                    $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                                    $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                                    $data_physician['physician_name_custom'] = $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'];
                                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                                    $data_physician['with'] = [
                                        'physician_name_custom' => $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'],
                                        'is_final_pmt' => $contract_data['is_final_payment'],
                                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                                        'hospital' => $invoice_result["hospital"]['name']
                                    ];
                                    $data_physician['subject_param'] = [
                                        'name' => '',
                                        'date' => '',
                                        'month' => $data_physician['month'],
                                        'year' => $data_physician['year'],
                                        'requested_by' => '',
                                        'manager' => '',
                                        'subjects' => ''
                                    ];

                                    $contracts = Contract::select('*')
                                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                        ->where('contracts.physician_id', '=', $contract_data['physician_id'])
                                        ->where('agreements.is_deleted', '=', 0)
                                        ->whereNull('contracts.deleted_at')
                                        ->count();

                                    if ($contracts > 0) {
                                        EmailQueueService::sendEmail($data_physician);
                                    }
                                }
                            }
                            Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                        } catch (Exception $e) {
                            Log::info('Message: ' . $e->getMessage());
                        }
                    }

                    Hospital::postHospitalsContractTotalSpendAndPaid($invoice_result["hospital"]->id);
                    UpdatePendingPaymentCount::dispatch($invoice_result["hospital"]->id);
                }
//
                return Response::json([
                    "status" => true,
                    "data" => $error_obj,
                    "errorCount" => $custome_invoice_error_count
                ]);
            } else {
                if (count($error_obj) > 0) {
                    return Response::json([
                        "status" => false,
                        "data" => $error_obj,
                        "errorCount" => $custome_invoice_error_count
                    ]);
                }else{
                    return Response::json([
                        "status" => true,
                        "data" => [],
                        "errorCount" => 0
                    ]);
                }
            }
        } else {
            $data = $this->addPaymentActual($data, $contract_ids, $practice_ids, $start_date, $end_date, $selected_date, $prev_amounts, $finalPayment, $localtimeZone, $result_hospital, $print_all_invoice_flag, $invoice_result);
            if ($data != 0) {
                $invoice_result = $data;
            }

            Hospital::postHospitalsContractTotalSpendAndPaid($hospital_id);
            UpdatePendingPaymentCount::dispatch($hospital_id);
            $invoice_result['print_all_invoice_flag'] = $print_all_invoice_flag;
            if ($print_all_invoice_flag == "true") {
                if (count($invoice_result) > 0) {
                    $last_invoice_no = $invoice_result["data"][0]["agreement_data"]["invoice_no"];
                    $start_date = mysql_date($invoice_result['start_date']);
                    $report_path = hospital_report_path($invoice_result["hospital"]);
                    $custom_date_with_random = date('mdYhis') + rand();

                    $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                    $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                    $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                    $report_filename = "Invoices_" . $invoice_result["hospital"]->name . "_" . $time_stamp_zone . "_" . $custom_date_with_random . ".pdf";
                    if (!File::exists($report_path)) {
                        File::makeDirectory($report_path, 0777, true, true);
                    };

                    try {
                        $customPaper = array(0, 0, 1683.78, 595.28);
                        $pdf = PDF::loadView('agreements/invoice_pdf', $invoice_result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                        $hospital_invoice = new HospitalInvoice;
                        $hospital_invoice->hospital_id = $invoice_result["hospital"]->id;
                        $hospital_invoice->filename = $report_filename;
                        $hospital_invoice->contracttype_id = 0;
                        $hospital_invoice->period = mysql_date(date("m/d/Y"));
                        $hospital_invoice->last_invoice_no = $last_invoice_no;
                        $hospital_invoice->save();

                        $report_id = $hospital_invoice->id;
                        $report_path = hospital_report_path($invoice_result["hospital"]);
                        $data['month'] = date("F", strtotime($start_date));
                        $data['year'] = date("Y", strtotime($start_date));
                        $data['name'] = "DYNAFIOS";
                        $data['email'] = $invoice_result["recipient"];
                        $data['file'] = $report_path . "/" . $report_filename;
                        SendEmail::dispatch($data);

                        foreach ($last_invoice_no = $invoice_result["data"][0]["practices"] as $practice_id => $practice) {
                            foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                                //Below code is added for sending the email to physician regarding payment submission (Akash)
                                $data_physician['month'] = date("F", strtotime($start_date));
                                $data_physician['year'] = date("Y", strtotime($start_date));
                                $data_physician['name'] = "DYNAFIOS";
                                $data_physician['email'] = $contract_data['physician_email'];
                                $data_physician['file'] = $report_path . "/" . $report_filename;
                                $data_physician['is_final_pmt'] = $contract_data['is_final_payment'];
                                $data_physician['amount_to_be_paid'] = $contract_data['amount_paid'];
                                $data_physician['hospital'] = $invoice_result["hospital"]['name'];
                                $data_physician['physician_name'] = $contract_data['physician_name'];
                                $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                                $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                                $data_physician['physician_name_custom'] = $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'];
                                $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                                $data_physician['with'] = [
                                    'physician_name_custom' => $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'],
                                    'is_final_pmt' => $contract_data['is_final_payment'],
                                    'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                                    'hospital_invoice_period_approval_date' => date("m/d/Y"),
                                    'hospital' => $invoice_result["hospital"]['name']
                                ];
                                $data_physician['subject_param'] = [
                                    'name' => '',
                                    'date' => '',
                                    'month' => $data_physician['month'],
                                    'year' => $data_physician['year'],
                                    'requested_by' => '',
                                    'manager' => '',
                                    'subjects' => ''
                                ];

                                $contracts = Contract::select('*')
                                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                    ->where('contracts.physician_id', '=', $contract_data['physician_id'])
                                    ->where('agreements.is_deleted', '=', 0)
                                    ->whereNull('contracts.deleted_at')
                                    ->count();

                                if ($contracts > 0) {
                                    EmailQueueService::sendEmail($data_physician);
                                }
                            }
                        }
                        Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                    } catch (Exception $e) {
                        Log::info('Message: ' . $e->getMessage());
                    }
                }

                Hospital::postHospitalsContractTotalSpendAndPaid($invoice_result["hospital"]->id);
                UpdatePendingPaymentCount::dispatch($invoice_result["hospital"]->id);
            }

            return Response::json([
                "status" => true,
                "data" => [],
                "errorCount" => 0
            ]);
        }
    }

    public function addPaymentActual($data, $contractId, $practiceId, $startDate, $endDate, $selectdDate, $prev_amount, $finalPayment, $localtimeZone, $hospital_obj, $print_all_invoice_flag, $invoice_result)
    {
        $data = $data;
        $contract_ids = $contractId;
        $practice_ids = $practiceId;
        $start_date = $startDate;
        $end_date = $endDate;
        $prev_amounts = $prev_amount;
        $selected_date = $selectdDate;
        $final_payment = $finalPayment;
        $amount_to_be_paid = "";
        $physician_name = "";

        $amountPaid = new Amount_paid();
        $result = $amountPaid->submitPayments($data, $contract_ids, $practice_ids, $start_date, $end_date, $selected_date, $prev_amounts, $final_payment, $localtimeZone);
        $result_is_lawson_interfaced = $result;
        $rehab_data = $result;
        if (isset($result["data"]) && count($result["data"]) > 0) {

            $queue_invoice_pdf = false;
            $queue_invoice_is_lawson_interfaced_pdf = false;
            $queue_invoice_rehab_pdf = false;
            $breakdown_count = 0;
            $contract_id_pdf = 0;
            foreach ($result["data"] as $key_data => $data) {
                foreach ($data["practices"] as $key_practice => $practice) {
                    $practice_data_for_payment_status = $practice["contract_data"];
                    foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                        $payment_physician_id = $contract_data['physician_id'];
                        $physician_name = $contract_data['physician_name'];
                        $amount_to_be_paid = $contract_data['amount_paid'];
                        $contract_id_pdf = $contract_data['contract_id'];
                        if ($contract_data['is_lawson_interfaced']) {
                            unset($result["data"][$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]);
                            $result["data"][$key_data]['practices'][$key_practice]['contract_data'] = array_values($result["data"][$key_data]['practices'][$key_practice]['contract_data']);
                            $queue_invoice_is_lawson_interfaced_pdf = true;
                        } else {
                            unset($result_is_lawson_interfaced["data"][$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]);
                            $result_is_lawson_interfaced["data"][$key_data]['practices'][$key_practice]['contract_data'] = array_values($result_is_lawson_interfaced["data"][$key_data]['practices'][$key_practice]['contract_data']);
                            $queue_invoice_pdf = true;

                            if ($contract_data['payment_type_id'] == PaymentType::REHAB) {
                                unset($result["data"][$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]);
                                $rehab_data["data"][$key_data]['practices'][$key_practice]['contract_data'][] = $contract_data;
                                $queue_invoice_rehab_pdf = true;

                                if (count($result["data"][$key_data]['practices'][$key_practice]['contract_data']) == 0) {
                                    $queue_invoice_pdf = false;
                                }
                            }
                            if ($result["hospital"]->invoice_type == 0) {
                                if ($final_payment != null && count($final_payment) > 0) {
                                    $result["data"][$key_data]['practices'][$key_practice]['contract_data'][0]['is_final_payment'] = $final_payment[0];
                                } else {
                                    $result["data"][$key_data]['practices'][$key_practice]['contract_data'][0]['is_final_payment'] = false;
                                }

                                if (count($invoice_result) > 0) {
                                    $existing_invoice_practice_ids = array_keys($invoice_result['data'][$key_data]['practices']);
                                    if (in_array($key_practice, $existing_invoice_practice_ids)) {

                                        $check_contract_obj_exists = true;
                                        foreach ($invoice_result['data'][$key_data]['practices'][$key_practice]['contract_data'] as $temp_contract_obj) {
                                            if ($temp_contract_obj['contract_id'] == $contract_data['contract_id'] && $temp_contract_obj['physician_id'] == $contract_data['physician_id'] && $temp_contract_obj['date_range'] == $contract_data['date_range']) {
                                                $check_contract_obj_exists = false;
                                            }
                                        }
                                        if ($check_contract_obj_exists) {
                                            $invoice_result['data'][$key_data]['practices'][$key_practice]['contract_data'][] = $contract_data;
                                        }
                                    } else {
                                        $invoice_result['data'][$key_data]['practices'][$key_practice] = $result["data"][$key_data]['practices'][$key_practice];
                                    }
                                } else {
                                    $invoice_result = $result;
                                    break;
                                }
                            }
                        }
                        // This will be used for dynamically increase the height of the custome invoice pdf page.
                        if ($breakdown_count == 0) {
                            $breakdown_count = count($contract_data['breakdown']);
                        }
                     
                    }
                }
            }

            /**
             * Below line of code is added for sending the push notification to physician on every invoice generation or payment submition. (Akash)
             */
            $data_physician = [];
            $physician_obj = Physician::findOrFail($payment_physician_id);
            $device_token_info = PhysicianDeviceToken::where("physician_id", "=", $physician_obj->id)->first();
            if ($device_token_info) {
                if ($final_payment == 'true') {
                    $push_msg = "The final payment of $" . $amount_to_be_paid . " has been submitted against your last approved logs for " . $hospital_obj->name;
                } else {
                    $push_msg = "The payment of $" . $amount_to_be_paid . " has been submitted against your last approved logs for " . $hospital_obj->name;
                }
                $notification_for = 'PAYMENT';

                try {
                    $result_one_signal = NotificationService::sendOneSignalPushNotification($device_token_info->device_token, $push_msg, $notification_for);
                } catch (Exception $e) {
                    Log::info("error", array($e));
                }
            }

            if ($queue_invoice_is_lawson_interfaced_pdf) {
                $last_invoice_no = $result_is_lawson_interfaced["data"][0]["agreement_data"]["invoice_no"] + 1;
           
                $filepath = storage_path("signatures_" . $last_invoice_no); //this fails
                if (!File::exists($filepath)) {
                    File::makeDirectory($filepath, 0777, true, true);
                };
                $start_date = mysql_date($result_is_lawson_interfaced['start_date']);
                $report_path = hospital_report_path($result_is_lawson_interfaced["hospital"]);
                $report_filename = "Interfaced_invoices_" . date('mdYhis') . ".pdf";
                
                if (!File::exists($report_path)) {
                    File::makeDirectory($report_path, 0777, true, true);
                };

                try {
                    $customPaper = array(0, 0, 1683.78, 595.28);
                    $pdf = PDF::loadView('agreements/lawson_interface_invoice_pdf', $result_is_lawson_interfaced)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);

                    $hospital_invoice = new HospitalInvoice;
                    $hospital_invoice->hospital_id = $result_is_lawson_interfaced["hospital"]->id;
                    $hospital_invoice->filename = $report_filename;
                    $hospital_invoice->contracttype_id = 0;
                    $hospital_invoice->period = mysql_date(date("m/d/Y"));
                    $hospital_invoice->last_invoice_no = $last_invoice_no;
                    $hospital_invoice->save();

                    $report_id = $hospital_invoice->id;
                    $report_path = hospital_report_path($result_is_lawson_interfaced["hospital"]);
                    $data['month'] = date("F", strtotime($start_date));
                    $data['year'] = date("Y", strtotime($start_date));
                    $data['name'] = "DYNAFIOS";
                    $data['email'] = $result_is_lawson_interfaced["recipient"];
                    $data['file'] = $report_path . "/" . $report_filename;
                    
                    SendEmail::dispatch($data);

                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                    $data_physician['month'] = date("F", strtotime($start_date));
                    $data_physician['year'] = date("Y", strtotime($start_date));
                    $data_physician['name'] = "DYNAFIOS";
                    $data_physician['email'] = $physician_obj->email;
                    $data_physician['file'] = $report_path . "/" . $report_filename;
                    $data_physician['is_final_pmt'] = $final_payment[0];
                    $data_physician['amount_to_be_paid'] = $amount_to_be_paid;
                    $data_physician['hospital'] = $hospital_obj->name;
                    $data_physician['physician_name_custom'] = $physician_obj->first_name . " " . $physician_obj->last_name;

                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN_;
                    $data_physician['with'] = [
                        'physician_name_custom' => $physician_obj->first_name . " " . $physician_obj->last_name,
                        'is_final_pmt' => $final_payment[0],
                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                        'hospital' => $hospital_obj->name
                    ];
                    $data_physician['subject_param'] = [
                        'name' => $hospital_obj->name,
                        'date' => '',
                        'month' => '',
                        'year' => '',
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];

                    EmailQueueService::sendEmail($data_physician);

                    Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                } catch (Exception $e) {
                    Log::info('Message: ' . $e->getMessage());
                }
              
            }

            $result["data"][0]["agreement_data"]["localtimeZone"] = $localtimeZone;

            //Standard/paper invoice report

            if ($queue_invoice_pdf) {
                $last_invoice_no = $result["data"][0]["agreement_data"]["invoice_no"];
             
                $filepath = storage_path("signatures_" . $last_invoice_no); //this fails
                if (!File::exists($filepath)) {
                    File::makeDirectory($filepath, 0777, true, true);
                };
                $start_date = mysql_date($result['start_date']);
                $report_path = hospital_report_path($result["hospital"]);
                $custom_date_with_random = date('mdYhis') + rand();

                $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                $report_filename = "Invoices_" . rand(1000, 9999) . "_" . $result["hospital"]->name . "_" . $time_stamp_zone . "_" . $contract_id_pdf . ".pdf";

                if (!File::exists($report_path)) {
                    File::makeDirectory($report_path, 0777, true, true);
                };
                $result['print_all_invoice_flag'] = false;
                try {
           
                    if ($result["hospital"]->invoice_type > 0) {
                        $default_height = 800;
                        if ($breakdown_count > 7) {
                            $req_increase_in_row = $breakdown_count - 7;
                            $default_height = $default_height + ($req_increase_in_row * 20);
                        }
                   
                        $customPaper = array(0, 0, $default_height, 595.28);
                        $pdf = PDF::loadView('agreements/custome_invoice_pdf', $result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                    } else {
                        $customPaper = array(0, 0, 1683.78, 595.28);
                        if ($print_all_invoice_flag == "false") {
                            $pdf = PDF::loadView('agreements/invoice_pdf', $result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                        } else {
                            return $invoice_result;
                        }

                    }
                    $hospital_invoice = new HospitalInvoice;
                    $hospital_invoice->hospital_id = $result["hospital"]->id;
                    $hospital_invoice->filename = $report_filename;
                    $hospital_invoice->contracttype_id = 0;
                    $hospital_invoice->period = mysql_date(date("m/d/Y"));
                    $hospital_invoice->last_invoice_no = $last_invoice_no;
                    $hospital_invoice->save();

                    $report_id = $hospital_invoice->id;
                    $report_path = hospital_report_path($result["hospital"]);
                    $data['month'] = date("F", strtotime($start_date));
                    $data['year'] = date("Y", strtotime($start_date));
                    $data['name'] = "DYNAFIOS";
                    $data['email'] = $result["recipient"];
                    $data['file'] = $report_path . "/" . $report_filename;
                    SendEmail::dispatch($data);
                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                    $data_physician['month'] = date("F", strtotime($start_date));
                    $data_physician['year'] = date("Y", strtotime($start_date));
                    $data_physician['name'] = "DYNAFIOS";
                    $data_physician['email'] = $physician_obj->email;
                    $data_physician['file'] = $report_path . "/" . $report_filename;
                    $data_physician['is_final_pmt'] = $final_payment[0];
                    $data_physician['amount_to_be_paid'] = $amount_to_be_paid;
                    $data_physician['hospital'] = $hospital_obj->name;
                    $data_physician['physician_name'] = $physician_name;
                    $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                    $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                    $data_physician['physician_name_custom'] = $physician_obj->first_name . " " . $physician_obj->last_name;
                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                    $data_physician['with'] = [
                        'physician_name_custom' => $physician_obj->first_name . " " . $physician_obj->last_name,
                        'is_final_pmt' => $final_payment[0],
                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                        'hospital' => $hospital_obj->name
                    ];
                    $data_physician['subject_param'] = [
                        'name' => '',
                        'date' => '',
                        'month' => $data_physician['month'],
                        'year' => $data_physician['year'],
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];

                    $contracts = Contract::select('*')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->where('contracts.physician_id', '=', $physician_obj->id)
                        ->where('agreements.is_deleted', '=', 0)
                        ->whereNull('contracts.deleted_at')
                        ->count();

                    if ($contracts > 0) {
                        EmailQueueService::sendEmail($data_physician);
                    }

                    Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                } catch (Exception $e) {
                    Log::info('Message: ' . $e->getMessage());
                }
            }

            if ($queue_invoice_rehab_pdf) {
                $last_invoice_no = $rehab_data["data"][0]["agreement_data"]["invoice_no"];
             
                $filepath = storage_path("signatures_" . $last_invoice_no); //this fails
                if (!File::exists($filepath)) {
                    File::makeDirectory($filepath, 0777, true, true);
                };
                $start_date = mysql_date($rehab_data['start_date']);
                $report_path = hospital_report_path($rehab_data["hospital"]);
                $custom_date_with_random = date('mdYhis') + rand();

                $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                $report_filename = "Invoices_" . $result["hospital"]->name . "_" . $time_stamp_zone . "_" . $contract_id_pdf . ".pdf";

               
                if (!File::exists($report_path)) {
                    File::makeDirectory($report_path, 0777, true, true);
                };
                try {
   
                    if ($result["hospital"]->invoice_type > 0) {
                        $default_height = 800;
                        if ($breakdown_count > 7) {
                            $req_increase_in_row = $breakdown_count - 7;
                            $default_height = $default_height + ($req_increase_in_row * 20);
                        }
                     
                        $customPaper = array(0, 0, $default_height, 595.28);
                        $pdf = PDF::loadView('agreements/custome_invoice_pdf', $result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                    } else {
                        $customPaper = array(0, 0, 1683.78, 1500);
                        $pdf = PDF::loadView('agreements/rehab_invoice_pdf', $rehab_data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);

                    }
    
                    $hospital_invoice = new HospitalInvoice;
                    $hospital_invoice->hospital_id = $rehab_data["hospital"]->id;
                    $hospital_invoice->filename = $report_filename;
                    $hospital_invoice->contracttype_id = 0;
                    $hospital_invoice->period = mysql_date(date("m/d/Y"));
                    $hospital_invoice->last_invoice_no = $last_invoice_no;
                    $hospital_invoice->save();
                    $report_id = $hospital_invoice->id;
                    $report_path = hospital_report_path($rehab_data["hospital"]);
                    $data['month'] = date("F", strtotime($start_date));
                    $data['year'] = date("Y", strtotime($start_date));
                    $data['name'] = "DYNAFIOS";
                    $data['email'] = $rehab_data["recipient"];
                    $data['file'] = $report_path . "/" . $report_filename;
                 
                    SendEmail::dispatch($data);

                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                    $data_physician['month'] = date("F", strtotime($start_date));
                    $data_physician['year'] = date("Y", strtotime($start_date));
                    $data_physician['name'] = "DYNAFIOS";
                    $data_physician['email'] = $physician_obj->email;
                    $data_physician['file'] = $report_path . "/" . $report_filename;
                    $data_physician['is_final_pmt'] = $final_payment[0];
                    $data_physician['amount_to_be_paid'] = $amount_to_be_paid;
                    $data_physician['hospital'] = $hospital_obj->name;
                    $data_physician['physician_name'] = $physician_name;
                    $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                    $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                    $data_physician['physician_name_custom'] = $physician_obj->first_name . " " . $physician_obj->last_name;
                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                    $data_physician['with'] = [
                        'physician_name_custom' => $physician_obj->first_name . " " . $physician_obj->last_name,
                        'is_final_pmt' => $final_payment[0],
                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                        'hospital' => $hospital_obj->name
                    ];
                    $data_physician['subject_param'] = [
                        'name' => '',
                        'date' => '',
                        'month' => $data_physician['month'],
                        'year' => $data_physician['year'],
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];

                    $contracts = Contract::select('*')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->where('contracts.physician_id', '=', $physician_obj->id)
                        ->where('agreements.is_deleted', '=', 0)
                        ->whereNull('contracts.deleted_at')
                        ->count();

                    if ($contracts > 0) {
                        EmailQueueService::sendEmail($data_physician);
                    }

                    Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                } catch (Exception $e) {
                    Log::info('Message: ' . $e->getMessage());
                }
            }
            //Update the payment status dashboard table
            if (count($practice_data_for_payment_status) > 0) {
                foreach ($practice_data_for_payment_status as $contract_dat_obj) {
                    $pmt_physician_id = $contract_data['physician_id'];
                    $pmt_practice_id = $contract_data['practice_id'];
                    $pmt_contract_id = $contract_data['contract_id'];
                    $pmt_contract_name_id = $contract_data['contract_name_id'];
                    $pmt_agreement_id = $contract_data['agreement_id'];
                    $pmt_hospital_id = $hospital_obj->id;
                }
            }
  
            UpdatePaymentStatusDashboard::dispatch($pmt_physician_id, $pmt_practice_id, $pmt_contract_id, $pmt_contract_name_id, $pmt_hospital_id, $pmt_agreement_id, $start_date);
        }
        return 0;
    }

    public function getHoursAndPaymentDetails()
    {
        $ids = Request::input('ids');
        $contract_ids = Request::input('contract_ids');
        $practice_ids = Request::input('practice_ids');
        $agreement_id = Request::input('agreement_id');
        $start_date = mysql_date(Request::input('start_date'));
        $end_date = mysql_date(Request::input('end_date'));
        $contract_month = Request::input('contract_month');
        $result = array();
        $worked_hours = array();
        $payment_details = array();
        $payment_done = array();
        $is_interfaced = array();
        $remaining_payment_details = array();
        $max_hours = array();
        $annual_max_pay = array();
        foreach ($ids as $key => $value) {
 
            $amount = DB::table('amount_paid')
                ->where('start_date', '<=', $start_date)
                ->where('end_date', '>=', $start_date)
                ->where('physician_id', '=', $value)
                ->where('contract_id', '=', $contract_ids[$key])
                ->where('practice_id', '=', $practice_ids[$key])
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();


            /*remaining payment*/
            $remaining = 0.0;
            $annual_remaining = 0.0;
            $hours = 0;
            $contracts = DB::table('contracts')->select('*')->where('id', $contract_ids[$key])->get();
            foreach ($contracts as $contract) {
                $flag = 0;
                $start_date = mysql_date(Request::input('start_date'));
                $end_date = mysql_date(Request::input('end_date'));

                $logs = PhysicianLog::select(
                    DB::raw("actions.name as action"),
                    DB::raw("actions.action_type_id as action_type_id"),
                    DB::raw("physician_logs.date as date"),
                    DB::raw("physician_logs.duration as worked_hours"),
                    DB::raw("physician_logs.signature as signature"),
                    DB::raw("physician_logs.approval_date as approval_date"),
                    DB::raw("physician_logs.details as notes")
                )
                    ->join("actions", "actions.id", "=", "physician_logs.action_id")
                    ->where("physician_logs.contract_id", "=", $contract->id)
                    ->where("physician_logs.physician_id", "=", $value)
                    ->where("physician_logs.practice_id", "=", $practice_ids[$key])
                    ->whereBetween("physician_logs.date", [$start_date, $end_date])
                    ->orderBy("physician_logs.date", "asc")
                    ->get();

                $calculated_payment = 0.0;
                $amount_paid = 0.0;

                if ($contract->payment_type_id != PaymentType::PER_DIEM) {
                    $rate = ContractRate::getRate($contract->id, $start_date, ContractRate::FMV_RATE);
                } else {
                    $weekdayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKDAY_RATE);
                    $weekendRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKEND_RATE);
                    $holidayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::HOLIDAY_RATE);
                    $oncallRate = ContractRate::getRate($contract->id, $start_date, ContractRate::ON_CALL_RATE);
                    $calledbackRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_BACK_RATE);
                    $calledInRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_IN_RATE);
                }

                $payment_type_id = $contract->payment_type_id;
                foreach ($logs as $log) {
                    if (($log->approval_date != '0000-00-00') || ($log->signature != 0)) {
                        $logduration = $log->worked_hours;
          
                        if ($contract->payment_type_id != PaymentType::PER_DIEM) {

                            if ($contract->payment_type_id == PaymentType::PSA) {
                                if ($contract->wrvu_payments) {
                                    $rate = Contract::getPsaRate($contract->id, $logduration);
                                }
                            }
                        } else {
                 

                            if (strlen(strstr(strtoupper($log->action), "WEEKDAY")) > 0) {
                             
                                $rate = $weekdayRate;
                            } else if (strlen(strstr(strtoupper($log->action), "WEEKEND")) > 0) {
                                $rate = $weekendRate;
                            } else if (strlen(strstr(strtoupper($log->action), "HOLIDAY")) > 0) {
                      
                                $rate = $holidayRate;
                            } else if ($log->action == "On-Call") {
                         
                                $rate = $oncallRate;
                            } else if ($log->action == "Called-Back") {
                             
                                $rate = $calledbackRate;
                            } else if ($log->action == "Called-In") {
                                $rate = $calledInRate;
                            }
                        }

                        $logpayment = $logduration * $rate;
                        $hours += $logduration;
                        $calculated_payment = $calculated_payment + $logpayment;
                    }

                }

                //amount paid from Hospital payment tab
                $amount_paid_hospital = DB::table('amount_paid')
                    ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"))
                    ->where('physician_id', '=', $value)
                    ->where('contract_id', '=', $contract_ids[$key])
                    ->where('practice_id', '=', $practice_ids[$key])
                    ->where("start_date", '=', $start_date)
                    ->where("end_date", '=', $end_date)
                    ->first();
                if ($amount_paid_hospital->amount_paid_hospital == null) {
                    $amount_paid_hospital->amount_paid_hospital = 0;
                }

                $amount_paid = $amount_paid_hospital->amount_paid_hospital;
              
                if ($contract->payment_type_id != PaymentType::STIPEND) {
                    $remaining += $calculated_payment - $amount_paid;
                } else {
                    $contract->rate = $rate;
                    $contract->amount_paid = $amount_paid;
                    $contract->worked_hours = $hours;
                    $contract->contract_month = $contract_month;
                    $contract->physician_id = $value;
                    $contract->month_end_date = $end_date;
                    $contract->practice_id = $practice_ids[$key];
                    $remaining = Agreement::getRemainingAmount($contract);
                }
            }
         
            array_push($worked_hours, $hours);
            array_push($remaining_payment_details, round($remaining, 2));

       

            $i = 0;
            if (count($amount) > 0) {
                foreach ($amount as $amount) {
                    $payment_details[$contract->id][$value][$i] = round($amount->amountPaid, 2);
                    $i++;
                    array_push($payment_done, 1);
                }
            } else {
                $payment_details[$contract->id][$value][$i] = round($remaining, 2);
                array_push($payment_done, 0);
            }

            if (isset($amount->is_interfaced)) {
                array_push($is_interfaced, $amount->is_interfaced);
            } else {
                array_push($is_interfaced, 0);
            }

            $max_hour = DB::table('contracts')
                ->where('physician_id', '=', $value)
                ->where("agreement_id", "=", $agreement_id)
                ->where("id", "=", $contract->id)
                ->first();
            if (isset($max_hour->max_hours)) {
                $days = days($start_date, $end_date);
                $monthly_max_hours = $max_hour->max_hours * $max_hour->rate;
                array_push($max_hours, round($monthly_max_hours, 2));
            }

            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $agreement = Agreement::findOrFail($contract->agreement_id);
                $contractDateBegin = date('Y-m-d', strtotime($agreement->start_date));
                $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($agreement->start_date)));
                $set = false;
                while (!$set) {
                    if ((date('Y-m-d', strtotime(Request::input('start_date'))) >= $contractDateBegin) && (date('Y-m-d', strtotime(Request::input('end_date'))) <= $contractDateEnd)) {
                        $set = true;
                    } else {
                        $contractDateBegin = $contractDateEnd;
                        $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contractDateBegin)));
                        $set = false;
                    }
                }
                $amount_paid_hospital_in_year = DB::table('amount_paid')
                    ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"))
                    ->where('contract_id', '=', $contract->id)
                    ->where("start_date", '>=', $contractDateBegin)
                    ->where("end_date", '<=', $contractDateEnd)
                    ->first();
                if ($amount_paid_hospital_in_year->amount_paid_hospital == null) {
                    $amount_paid_hospital_in_year->amount_paid_hospital = 0;
                }
                $expected_payment_to_be_paid = $contract->annual_cap * $contract->rate;
                $annual_remaining = $expected_payment_to_be_paid - $amount_paid_hospital_in_year->amount_paid_hospital;
                if ($annual_remaining < 0) {
                    array_push($annual_max_pay, 0.0);
                } else {
                    array_push($annual_max_pay, round($annual_remaining, 2));
                }
            } else {
                array_push($annual_max_pay, 0.0);
            }
        }
        $result["worked_hours"] = $worked_hours;
        $result["payment_details"] = $payment_details;
        $result["monthly_max_hours"] = $max_hours;
        $result["remaining_amount"] = $remaining_payment_details;
        $result["payment_done"] = $payment_done;
        $result["annual_max_pay"] = $annual_max_pay;
        $result["is_interfaced"] = $is_interfaced;
        $result["payment_type_id"] = $payment_type_id;
        echo json_encode($result);
        die;
    }

    public function getPaymentDetails()
    {
        $ids = Request::input('ids');
        $start_date = mysql_date(Request::input('start_date'));
  
        $end_date = mysql_date(Request::input('end_date'));
        $vals = array();
        foreach ($ids as $key => $value) {
            $amount = DB::table('amount_paid')
                ->where('start_date', '<=', $start_date)
                ->where('end_date', '>=', $start_date)
                ->where('physician_id', '=', $value)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if (isset($amount->amountPaid) && count($amount->amountPaid) > 0)
                array_push($vals, round($amount->amountPaid, 2));
        }
        echo json_encode($vals);
        die;
    }

    public function getPayment($id)
    {
        set_time_limit(0);
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $data['hospital'] = $hospital;

        if ($hospital->approve_all_invoices) {
            return View::make('hospitals/payments_all')->with($data);
        } else {
            $agreements_paylist = Agreement::getPaymentRequireInfo($hospital->id);
            $data['agreements'] = $agreements_paylist['agreement_list'];
            if (count($data['agreements']) > 0) {
                $data['contract_period_list'] = $agreements_paylist['contract_period_list'];
            } else {
                $data['contracts'] = array();
            }

            if (Request::ajax()) {
                return Response::json($data);
            }

            return View::make('hospitals/payments')->with($data);
        }
    }

    public function getPaymentDetailsForInvoiceDashboard($id)
    {
        set_time_limit(0);
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $data['hospital'] = $hospital;
        $agreements_paylist = Agreement::getPaymentRemainingInfo($hospital->id);
        $data['agreements'] = $agreements_paylist['agreement_list'];

        if ($hospital->approve_all_invoices) {
            if (count($data['agreements']) > 0) {
                $agreement_ids = array_keys($data['agreements']);
                $practice_list = [];
                $payment_type_list = [];
                $contract_type_list = [];
                $physician_list = [];
                $agreement_min_date = date('m/d/Y', strtotime(now()));
                $agreement_max_date = date('m/d/Y', strtotime(now()));

                foreach ($agreement_ids as $agreement_id) {
                    $agreement = Agreement::FindOrFail($agreement_id);

                    $practices = $agreements_paylist['agreement_data_list'][$agreement_id]['practices'];
                    foreach ($practices as $key => $practices) {
                        if (!array_key_exists($key, $practice_list)) {
                            $practice_list[$key] = $practices;
                        }
                    }

                    $payment_types = $agreements_paylist['agreement_data_list'][$agreement_id]['payment_type_list'];
                    foreach ($payment_types as $key => $payment_type) {
                        if (!array_key_exists($key, $payment_type_list)) {
                            $payment_type_list[$key] = $payment_type;
                        }
                    }

                    $contract_types = $agreements_paylist['agreement_data_list'][$agreement_id]['contract_type_list'];
                    foreach ($contract_types as $key => $contract_type) {
                        if (!array_key_exists($key, $contract_type_list)) {
                            $contract_type_list[$key] = $contract_type;
                        }
                    }

                    $physicians = $agreements_paylist['agreement_data_list'][$agreement_id]['physician_list'];
                    foreach ($physicians as $key => $physician) {
                        if (!array_key_exists($key, $physician_list)) {
                            $physician_list[$key] = $physician;
                        }
                    }
                }

                $data['contract_list'] = $agreements_paylist['contract_list'];
                $data['practice_list'] = $practice_list;
                $practice_id = Request::input('p_id', key($data['practice_list']));
                $data['payment_type_list'] = $payment_type_list;
                $payment_type_id = Request::input('t_id', key($data['payment_type_list']));
                $data['contract_type_list'] = $contract_type_list;
                $contract_type_id = Request::input('ct_id', key($data['contract_type_list']));
                $data['physician_list'] = $physician_list;
                $physician_id = Request::input('phy_id', key($data['physician_list']));
                $data['practice_id'] = $practice_id;
                $data['payment_type_id'] = $payment_type_id;
                $data['contract_type_id'] = $contract_type_id;
                $data['physician_id'] = $physician_id;
                $data['contract_list'] = $agreements_paylist['contract_list'];
                $agreement_id = intval(Request::input('a_id', key($data['agreements'])));
                $data['dates_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['dates'];
                $current_month_number = $agreements_paylist['agreement_data_list'][$agreement_id]['current_month'];
                $month_number = Request::input('m_id', $current_month_number);
                $data['selected_agreement_id'] = $agreement_id;
                $data['current_month'] = $month_number;
                $cname_id = Request::input('cn_id', 0);

                $agreement_min_date = date('m/d/Y', strtotime(now() . ' - 24 months'));
                $agreement_min_date = date('m/01/Y', strtotime($agreement_min_date));
                $agreement_max_date = date('m/d/Y', strtotime(now()));
                $start_date = Request::input('start_date', $agreement_min_date);
                $end_date = Request::input('end_date', $agreement_max_date);
                $data['start_date'] = $start_date;
                $data['end_date'] = $end_date;
                $data['min_date'] = $agreement_min_date;
                $data['max_date'] = $agreement_max_date;

                $agreement_data = Agreement::getAllAgreementPaymentRequireInfo($hospital->id, $practice_id, $payment_type_id, $contract_type_id, $physician_id, $start_date, $end_date);

                if ($agreement_data) {
                    $data['contracts'] = $agreement_data['contracts_data'];
                } else {
                    $data['contracts'] = array();
                }
            } else {
                $data['contracts'] = array();
            }
            $data['table'] = View::make('hospitals/_invoiceAll')->with($data)->render();
            return View::make('hospitals/_requirePaymentAll')->with($data);
        } else {
            if (count($data['agreements']) > 0) {
                $data['contract_list'] = $agreements_paylist['contract_list'];
                $agreement_id = intval(Request::input('a_id', key($data['agreements'])));
                $data['dates_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['dates'];

                $current_month_number = $agreements_paylist['agreement_data_list'][$agreement_id]['current_month'];
                $data['practice_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['practices'];
                $practice_id = Request::input('p_id', key($data['practice_list']));
                $data['payment_type_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['payment_type_list'];
                $data['contract_type_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['contract_type_list'];
                $contract_type_id = Request::input('ct_id', key($data['contract_type_list']));
                $data['physician_list'] = $agreements_paylist['agreement_data_list'][$agreement_id]['physician_list'];
                $month_number = Request::input('m_id', $current_month_number);
                $payment_type_id = Request::input('t_id', key($data['payment_type_list']));
                $physician_id = Request::input('phy_id', key($data['physician_list']));
                $data['selected_agreement_id'] = $agreement_id;
                $data['practice_id'] = $practice_id;
                $data['payment_type_id'] = $payment_type_id;
                $data['contract_type_id'] = $contract_type_id;
                $data['physician_id'] = $physician_id;
                $data['current_month'] = $month_number;
                $cname_id = Request::input('cn_id', 0);
                $agreement_data = Agreement::getAgreementPaymentRequireInfo($agreement_id, $practice_id, $payment_type_id, $contract_type_id, $physician_id, $month_number, $cname_id);
                $data['contracts'] = $agreement_data['contracts_data'];
            } else {
                $data['contracts'] = array();
            }
            $data['table'] = View::make('hospitals/_invoice')->with($data)->render();
            return View::make('hospitals/_requirePayment')->with($data);
        }
    }

    public function getEdit($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $groups = [
            '2' => Group::findOrFail(2)->name,
            '5' => Group::findOrFail(5)->name
        ];
        $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->where('hospital_user.hospital_id', '=', $hospital->id)
            ->where('group_id', '!=', Group::Physicians)
            ->orderBy("name")
            ->pluck('name', 'id');
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[-1] = "Add New User";
        $users_for_invoice_recipients = $users;
        $users_for_invoice_recipients[0] = "NA";

        $invoice_receipient = explode(',', $agreement->invoice_receipient);

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        $ApprovalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $id)
            ->where('contract_id', "=", 0)
            ->where('is_deleted', '=', '0')
            ->orderBy('level')->get();
        
        $internal_notes = AgreementInternalNote::
        where('agreement_id', '=', $agreement->id)
            ->value('note');

        $payment_frequency_option = [
            '1' => 'Monthly',
            '2' => 'Weekly',
            '3' => 'Bi-Weekly',
            '4' => 'Quarterly'
        ];

        if ($agreement->payment_frequency_type == 1) {
            $review_day_range_limit = 28;
            $initial_review_day=10;
            $final_review_day=20;
        } else if ($agreement->payment_frequency_type == 2) {
            $review_day_range_limit = 6;
            $initial_review_day=2;
            $final_review_day=6;
        } else if ($agreement->payment_frequency_type == 3) {
            $review_day_range_limit = 12;
            $initial_review_day=2;
            $final_review_day=12;
        } else if ($agreement->payment_frequency_type == 4) {
            $review_day_range_limit = 85;
            $initial_review_day=10;
            $final_review_day=20;
        }

        $data = [
            'hospital' => $hospital,
            'agreement' => $agreement,
            'users' => $users,
            'groups' => $groups,
            'invoice_receipient' => $invoice_receipient,
            'approval_manager_type' => $approval_manager_type,
            'ApprovalManagerInfo' => $ApprovalManagerInfo,
            'users_for_invoice_recipients' => $users_for_invoice_recipients,
            'internal_notes' => $internal_notes,
            'payment_frequency_option' => $payment_frequency_option,
            'review_day_range_limit' => $review_day_range_limit,
            'initial_review_day' => $initial_review_day,
            'final_review_day' => $final_review_day
        ];

        return View::make('agreements/edit')->with($data);
    }

    public function postEdit($id)
    {

        $agreement = Agreement::findOrFail($id);
        $agreement->id = $id;
        $hospital = $agreement->hospital;
        $hospital_details = Hospital::findOrFail($hospital->id);
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new AgreementValidation();
        $emailvalidation = new EmailValidation();
        if (Request::has("on_off")) {
            if (Request::input("on_off") == 1) {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateEdit(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                    if (!$emailvalidation->validateEmailDomain(Request::input())) {
                        return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                    }
                } else {
                    if (!$validation->validateEditforInvoiceOnOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                }
            } else {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                    if (!$emailvalidation->validateEmailDomain(Request::input())) {
                        return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                    }
                } else {
                    if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }

                }
            }
        } else {
            if ($hospital_details->invoice_dashboard_on_off == 1) {
                if (!$validation->validateOff(Request::input())) {
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }
            } else {
                if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }

            }
        }

        $i = 1;
        $approver_mgr_err = [];
        for ($i = 1; $i < 7; $i++) {
            $manager = Request::input('approval_manager_level' . $i);
            if ($manager == null || $manager == "") {
                $approver_mgr_err['approval_manager_level' . $i] = ['Approver manager cannot be empty.'];
            }
        }
        if (count($approver_mgr_err) > 0) {
            return Redirect::back()
                ->withErrors($approver_mgr_err)
                ->withInput();
        }

        if (strtotime(Request::input('frequency_start_date')) > strtotime(Request::input('start_date'))) {
            return Redirect::back()->with([
                'error' => "Payment frequency date should be before or same with agreement start date."
            ])->withInput();
        }

        $agreementModel = new Agreement;
        $response = $agreementModel->updateAgreement($agreement);

        if ($response["response"] === "error") {
            return Redirect::back()->with([
                'error' => $response["msg"]
            ])->withInput();
        } else {
            return Redirect::route('agreements.show', $agreement->id)
                ->with(['success' => $response["msg"]]);
        }


    }

    public function getRenew($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        if (!$this->isAgreementExpiring($agreement)) {
            return Redirect::back()->with([
                'error' => Lang::get('agreements.renew_error')
            ]);
        }

        $groups = [
            '2' => Group::findOrFail(2)->name,
            '5' => Group::findOrFail(5)->name
        ];

        $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->where('hospital_user.hospital_id', '=', $hospital->id)
            ->where('group_id', '!=', Group::Physicians)
            ->orderBy("name")
            ->pluck('name', 'id');
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[-1] = "Add New User";
        $users[0] = "NA";

        $invoice_receipient = explode(',', $agreement->invoice_receipient);

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        $ApprovalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $id)
            ->where('is_deleted', '=', '0')
            ->where('contract_id', '=', 0)
            ->orderBy('level')->get();

        $payment_frequency_option = [
            '1' => 'Monthly',
            '2' => 'Weekly',
            '3' => 'Bi-Weekly',
            '4' => 'Quarterly'
        ];

        if ($agreement->payment_frequency_type == 1) {
            $review_day_range_limit = 28;
        } else if ($agreement->payment_frequency_type == 2) {
            $review_day_range_limit = 6;
        } else if ($agreement->payment_frequency_type == 3) {
            $review_day_range_limit = 12;
        } else if ($agreement->payment_frequency_type == 4) {
            $review_day_range_limit = 85;
        }

        $data['hospital'] = $hospital;
        $data['agreement'] = $agreement;
        $data['users'] = $users;
        $data['groups'] = $groups;
        $data['invoice_receipient'] = $invoice_receipient;
        $data['approval_manager_type'] = $approval_manager_type;
        $data['ApprovalManagerInfo'] = $ApprovalManagerInfo;
        $data['payment_frequency_option'] = $payment_frequency_option;

        $data['review_day_range_limit'] = $review_day_range_limit;

        return View::make('agreements/renew')->with($data);
    }

    public function postRenew($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;
        $hospital_details = Hospital::findOrFail($hospital->id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        if (!$this->isAgreementExpiring($agreement)) {
            return Redirect::back()->with([
                'error' => Lang::get('agreements.renew_error')
            ])->withInput();
        }

        $validation = new AgreementValidation();
        $emailvalidation = new EmailValidation();
        if (Request::has("on_off")) {
            if (Request::input("on_off") == 1) {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateRenew(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                    if (!$emailvalidation->validateEmailDomain(Request::input())) {
                        return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                    }
                } else {
                    if (!$validation->validateRenewforInvoiceOnOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                }
            } else {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                    if (!$emailvalidation->validateEmailDomain(Request::input())) {
                        return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                    }
                } else {
                    if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                }
            }
        } else {
            if ($hospital_details->invoice_dashboard_on_off == 1) {
                if (!$validation->validateOff(Request::input())) {
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }
            } else {
                if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }
            }
        }

        if (strtotime(Request::input('frequency_start_date')) > strtotime(Request::input('start_date'))) {
            return Redirect::back()->with([
                'error' => "Payment frequency date should be before or same with agreement start date."
            ])->withInput();
        }


        $agreementModel = new Agreement;
        $response = $agreementModel->renewAgreement($agreement, $hospital);
        if ($response["response"] === "error") {
            return Redirect::back()->with([
                'error' => $response["msg"]
            ])->withInput();
        } else {
            return Redirect::route('agreements.show', $response["new_agreemnet_id"])
                ->with(['success' => $response["msg"]]);
        }


    }

    public function postArchive($id)
    {
        $agreement = Agreement::findOrFail($id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        if (!$this->isAgreementExpired($agreement)) {
            return Redirect::back()->with([
                'error' => Lang::get('agreements.archive_error')
            ])->withInput();
        }

        $agreement->archived = true;
        $agreement->save();

        return Redirect::route('agreements.show', $agreement->id)->with([
            'success' => Lang::get('agreements.archive_success')
        ]);
    }

    public function getDelete($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        foreach ($agreement->contracts as $contract) {
            $checkPenddingLogs = PhysicianLog::penddingApprovalForContract($contract->id);
            if ($checkPenddingLogs) {
                return Redirect::back()->with([
                    'error' => Lang::get('agreements.approval_pending_error')
                ]);
            }
            $checkPenddingPayments = Amount_paid::penddingPaymentForContract($contract->id);
            if ($checkPenddingPayments) {
                return Redirect::back()->with([
                    'error' => Lang::get('agreements.payment_pending_error')
                ]);
            }
        }

       
        $agreement->is_deleted = true;

        $agreement->save();

        return Redirect::route('hospitals.agreements', $hospital->id)->with([
            'success' => Lang::get('agreements.delete_success')
        ]);
    }

    public function getCopy($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = $agreement->hospital;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $agreementModel = new Agreement;
        $response = $agreementModel->copyAgreement($agreement, $hospital);
        if ($response["response"] === "error") {
            return Redirect::back()->with([
                'error' => $response["msg"]
            ])->withInput();
        } else {
            return Redirect::route('agreements.show', $response["new_agreement_id"])
                ->with(['success' => $response["msg"]]);
        }
    }

    public function postUnarchive($id)
    {
        $agreement = Agreement::findOrFail($id);
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $agreement->archived = false;
        if (!$agreement->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('agreements.unarchive_error')
            ]);
        } else {
            return Redirect::route('agreements.show', $agreement->id)->with([
                'success' => Lang::get('agreements.unarchive_success')
            ]);
        }
    }

    public function addPaymentAll()
    {
        $ids = Request::input('ids');                                           // Physician Ids
        $data = Request::input('vals');                                         // Payment Amount
        $contract_ids = Request::input('contract_ids');                         // Contract Ids
        $practice_ids = Request::input('practice_ids');                         // Practice Ids
        $prev_amounts = Request::input('prev_values');                           // Previous Amounts

        $selected_date = Request::input('selected_date');
        $hospital_id = Request::input('hospital_id');                           // Hospital_id
        $finalPayment = Request::input('finalPayment');                         // Final payment flag
        $timestamp = Request::input("timestamp");
        $timeZone = Request::input("timeZone");
        $start_dates = Request::input('start_date');
        $end_dates = Request::input('end_date');
        $print_all_invoice_flag = Request::input('print_all_invoice_flag');

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

        $error_obj = [];
        $hospital_invoice_error_arr = [];
        $physician_invoice_error_arr = [];
        $contract_invoice_error_arr = [];
        $practice_invoice_error_arr = [];
        $invoice_result = [];

        $result_hospital = Hospital::findOrFail($hospital_id);

        $payment_data_arr = [];
        $custome_invoice_error_count = 0;

        if ($contract_ids != null) {
            if (count($contract_ids) > 0) {
                foreach ($contract_ids as $index => $contract_id) {
                    $check_final_payment = Amount_paid::where('contract_id', '=', $contract_id)
                        ->where('practice_id', '=', $practice_ids[$index])
                        ->where('start_date', '=', $start_dates[$index])
                        ->where('end_date', '=', $end_dates[$index])
                        ->where('final_payment', '=', 1)->get();

                    if(count($check_final_payment) == 0){
                        $temp_arr = [];
                        $temp_arr['contractId'] = [$contract_id];
                        $temp_arr['physicianId'] = [$ids[$index]];
                        $temp_arr['data'] = [$data[$index]];
                        $temp_arr['practiceId'] = [$practice_ids[$index]];
                        $temp_arr['startDate'] = $start_dates[$index];                  // $start_date;
                        $temp_arr['endDate'] = $end_dates[$index];                    // $end_date;
                        $temp_arr['selectedDate'] = $selected_date;
                        $temp_arr['prev_amount'] = [];
                        $temp_arr['finalPayment'] = [$finalPayment[$index]];
    
                        if (!array_key_exists($contract_id . '_' . $practice_ids[$index] . '_' . $start_dates[$index] . '_' . $end_dates[$index], $payment_data_arr)) {
                            $payment_data_arr[$contract_id . '_' . $practice_ids[$index] . '_' . $start_dates[$index] . '_' . $end_dates[$index]] = $temp_arr;
                        }
                    }
                }
            }

            if ($prev_amounts != null) {
                if (count($prev_amounts) > 0) {
                    foreach ($prev_amounts as $prev_amount) {
                        $temp_arr_prev = [];
                        $amount_paid_obj = Amount_paid::where('id', '=', $prev_amount['id'])->where('final_payment', '=', 0)->first();
                        if ($amount_paid_obj) {
                            if (array_key_exists($amount_paid_obj->contract_id . '_' . $amount_paid_obj->practice_id . '_' . $amount_paid_obj->start_date . '_' . $amount_paid_obj->end_date, $payment_data_arr)) {
                                $payment_data_arr[$amount_paid_obj->contract_id . '_' . $amount_paid_obj->practice_id . '_' . $amount_paid_obj->start_date . '_' . $amount_paid_obj->end_date]['prev_amount'][] = $prev_amount;
                            }
                        }
                    }
                }
            }

            if (count($payment_data_arr) > 0) {
                foreach ($payment_data_arr as $paymentObj) {
                    // Below function is used for extracting key as variable and value as its value and then those variables is passed to the given function.
                    extract($paymentObj);

                    $contract = Contract::whereIn('id', $contractId)->first();
                    if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                        $agreement = Agreement::FindOrFail($contract->agreement_id);
                        $temp_start_date = $agreement->start_date;
                        while ($temp_start_date <= $endDate) {
                            $temp_end_date = date('Y-m-d', strtotime('+1 year', strtotime($temp_start_date)));
                            $temp_end_date = date('Y-m-d', strtotime('-1 days', strtotime($temp_end_date)));
                            if ($startDate >= $temp_start_date and $startDate <= $temp_end_date) {
                                break;
                            } else {
                                $temp_start_date = date('Y-m-d', strtotime('+1 year', strtotime($temp_start_date)));
                            }
                        }

                        if ($temp_end_date >= now()) {
                            $temp_end_date = now();
                        }

                        $sum_amount_paid_cumulative = Amount_paid::select(DB::raw("sum(amountPaid) as sum_amount_paid"))
                            ->where('contract_id', '=', $contract->id)
                            ->whereIn('practice_id', $practiceId)
                            ->where('start_date', '>=', $temp_start_date)
                            ->where('end_date', '<=', $temp_end_date)
                            ->first();

                        $sum_amount_paid = $sum_amount_paid_cumulative->sum_amount_paid != null ? $sum_amount_paid_cumulative->sum_amount_paid : 0.00;
                        $remaining_amount = $contract->annual_max_payment - $sum_amount_paid;
                        $amount = implode(',', $data);
                        if ($contract->annual_max_payment > 0) {
                            if ($remaining_amount > 0 && $amount <= $remaining_amount) {
                                $result = $this->addPaymentActual($data, $contractId, $practiceId, $startDate, $endDate, $selectedDate, $prev_amount, $finalPayment, $localtimeZone, $result_hospital, $print_all_invoice_flag, $invoice_result);
                            }else{
                                $result = 0;
                            }
                        }else{
                            $result = 0;
                        }
                    } else {
                        $result = $this->addPaymentActual($data, $contractId, $practiceId, $startDate, $endDate, $selectedDate, $prev_amount, $finalPayment, $localtimeZone, $result_hospital, $print_all_invoice_flag, $invoice_result);
                    }

                    if ($result != 0) {
                        $invoice_result = $result;
                    }
                }

                $invoice_result['print_all_invoice_flag'] = $print_all_invoice_flag;

                if ($print_all_invoice_flag == "true" && $result_hospital->invoice_type != 1) {
                    if (count($invoice_result) > 0) {
                        $last_invoice_no = $invoice_result["data"][0]["agreement_data"]["invoice_no"];
                        $start_date = mysql_date($invoice_result['start_date']);
                        $report_path = hospital_report_path($invoice_result["hospital"]);
                        $custom_date_with_random = date('mdYhis') + rand();

                        $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                        $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                        $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                        $report_filename = "Invoices_" . $invoice_result["hospital"]->name . "_" . $time_stamp_zone . "_" . $custom_date_with_random . ".pdf";
                        if (!File::exists($report_path)) {
                            File::makeDirectory($report_path, 0777, true, true);
                        };

                        try {
                            $customPaper = array(0, 0, 1683.78, 595.28);
                            $pdf = PDF::loadView('agreements/invoice_pdf', $invoice_result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                            $hospital_invoice = new HospitalInvoice;
                            $hospital_invoice->hospital_id = $invoice_result["hospital"]->id;
                            $hospital_invoice->filename = $report_filename;
                            $hospital_invoice->contracttype_id = 0;
                            $hospital_invoice->period = mysql_date(date("m/d/Y"));
                            $hospital_invoice->last_invoice_no = $last_invoice_no;
                            $hospital_invoice->save();

                            $report_id = $hospital_invoice->id;
                            $report_path = hospital_report_path($invoice_result["hospital"]);
                            $data['month'] = date("F", strtotime($start_date));
                            $data['year'] = date("Y", strtotime($start_date));
                            $data['name'] = "DYNAFIOS";
                            $data['email'] = $invoice_result["recipient"];
                            $data['file'] = $report_path . "/" . $report_filename;
                            SendEmail::dispatch($data);

                            foreach ($last_invoice_no = $invoice_result["data"][0]["practices"] as $practice_id => $practice) {
                                foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                                    $data_physician['month'] = date("F", strtotime($start_date));
                                    $data_physician['year'] = date("Y", strtotime($start_date));
                                    $data_physician['name'] = "DYNAFIOS";
                                    $data_physician['email'] = $contract_data['physician_email'];
                                    $data_physician['file'] = $report_path . "/" . $report_filename;
                                    $data_physician['is_final_pmt'] = $contract_data['is_final_payment'];
                                    $data_physician['amount_to_be_paid'] = $contract_data['amount_paid'];
                                    $data_physician['hospital'] = $invoice_result["hospital"]['name'];
                                    $data_physician['physician_name'] = $contract_data['physician_name'];
                                    $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                                    $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                                    $data_physician['physician_name_custom'] = $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'];
                                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                                    $data_physician['with'] = [
                                        'physician_name_custom' => $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'],
                                        'is_final_pmt' => $contract_data['is_final_payment'],
                                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                                        'hospital' => $invoice_result["hospital"]['name']
                                    ];
                                    $data_physician['subject_param'] = [
                                        'name' => '',
                                        'date' => '',
                                        'month' => $data_physician['month'],
                                        'year' => $data_physician['year'],
                                        'requested_by' => '',
                                        'manager' => '',
                                        'subjects' => ''
                                    ];

                                    $contracts = Contract::select('*')
                                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                        ->where('contracts.physician_id', '=', $contract_data['physician_id'])
                                        ->where('agreements.is_deleted', '=', 0)
                                        ->whereNull('contracts.deleted_at')
                                        ->count();

                                    if ($contracts > 0) {
                                        EmailQueueService::sendEmail($data_physician);
                                    }
                                }
                            }
                            Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                        } catch (Exception $e) {
                            Log::info('Message: ' . $e->getMessage());
                        }
                    }

                    Hospital::postHospitalsContractTotalSpendAndPaid($invoice_result["hospital"]->id);
                    UpdatePendingPaymentCount::dispatch($invoice_result["hospital"]->id);
                }

                return Response::json([
                    "status" => true,
                    "data" => $error_obj,
                    "errorCount" => $custome_invoice_error_count
                ]);
            } else {
                if (count($error_obj) > 0) {
                    return Response::json([
                        "status" => false,
                        "data" => $error_obj,
                        "errorCount" => $custome_invoice_error_count
                    ]);
                }else{
                    return Response::json([
                        "status" => true,
                        "data" => [],
                        "errorCount" => 0
                    ]);
                }
            }
        } else {
            if (count($prev_amounts) > 0) {
                foreach ($prev_amounts as $prev_amount) {
                    $result = $this->addPaymentActual($data, $contract_ids, $practice_ids, $prev_amount['startPeriod'], $prev_amount['endPeriod'], $selected_date, $prev_amounts, $finalPayment, $localtimeZone, $result_hospital, $print_all_invoice_flag, $invoice_result);
                    if ($result != 0) {
                        $invoice_result = $result;
                    }

                    Hospital::postHospitalsContractTotalSpendAndPaid($hospital_id);
                    UpdatePendingPaymentCount::dispatch($hospital_id);
                }
                $invoice_result['print_all_invoice_flag'] = $print_all_invoice_flag;
                if ($print_all_invoice_flag == "true") {
                    if (count($invoice_result) > 0) {
                        $last_invoice_no = $invoice_result["data"][0]["agreement_data"]["invoice_no"];
                        $start_date = mysql_date($invoice_result['start_date']);
                        $report_path = hospital_report_path($invoice_result["hospital"]);
                        $custom_date_with_random = date('mdYhis') + rand();

                        $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
                        $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
                        $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
                        $report_filename = "Invoices_" . $invoice_result["hospital"]->name . "_" . $time_stamp_zone . "_" . $custom_date_with_random . ".pdf";
                        if (!File::exists($report_path)) {
                            File::makeDirectory($report_path, 0777, true, true);
                        };

                        try {
                            $customPaper = array(0, 0, 1683.78, 595.28);
                            $pdf = PDF::loadView('agreements/invoice_pdf', $invoice_result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                            $hospital_invoice = new HospitalInvoice;
                            $hospital_invoice->hospital_id = $invoice_result["hospital"]->id;
                            $hospital_invoice->filename = $report_filename;
                            $hospital_invoice->contracttype_id = 0;
                            $hospital_invoice->period = mysql_date(date("m/d/Y"));
                            $hospital_invoice->last_invoice_no = $last_invoice_no;
                            $hospital_invoice->save();

                            $report_id = $hospital_invoice->id;
                            $report_path = hospital_report_path($invoice_result["hospital"]);
                            $data['month'] = date("F", strtotime($start_date));
                            $data['year'] = date("Y", strtotime($start_date));
                            $data['name'] = "DYNAFIOS";
                            $data['email'] = $invoice_result["recipient"];
                            $data['file'] = $report_path . "/" . $report_filename;
                            SendEmail::dispatch($data);

                            foreach ($last_invoice_no = $invoice_result["data"][0]["practices"] as $practice_id => $practice) {
                                foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                                    //Below code is added for sending the email to physician regarding payment submission (Akash)
                                    $data_physician['month'] = date("F", strtotime($start_date));
                                    $data_physician['year'] = date("Y", strtotime($start_date));
                                    $data_physician['name'] = "DYNAFIOS";
                                    $data_physician['email'] = $contract_data['physician_email'];
                                    $data_physician['file'] = $report_path . "/" . $report_filename;
                                    $data_physician['is_final_pmt'] = $contract_data['is_final_payment'];
                                    $data_physician['amount_to_be_paid'] = $contract_data['amount_paid'];
                                    $data_physician['hospital'] = $invoice_result["hospital"]['name'];
                                    $data_physician['physician_name'] = $contract_data['physician_name'];
                                    $data_physician['hospital_invoice_period'] = $data_physician['month'] . " " . $data_physician['year'];
                                    $data_physician['hospital_invoice_period_approval_date'] = date("m/d/Y");

                                    $data_physician['physician_name_custom'] = $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'];
                                    $data_physician['type'] = EmailSetup::EMAIL_INVOICE_PHYSICIAN;
                                    $data_physician['with'] = [
                                        'physician_name_custom' => $contract_data['physician_first_name'] . " " . $contract_data['physician_last_name'],
                                        'is_final_pmt' => $contract_data['is_final_payment'],
                                        'hospital_invoice_period' => $data_physician['month'] . " " . $data_physician['year'],
                                        'hospital_invoice_period_approval_date' => date("m/d/Y"),
                                        'hospital' => $invoice_result["hospital"]['name']
                                    ];
                                    $data_physician['subject_param'] = [
                                        'name' => '',
                                        'date' => '',
                                        'month' => $data_physician['month'],
                                        'year' => $data_physician['year'],
                                        'requested_by' => '',
                                        'manager' => '',
                                        'subjects' => ''
                                    ];

                                    $contracts = Contract::select('*')
                                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                        ->where('contracts.physician_id', '=', $contract_data['physician_id'])
                                        ->where('agreements.is_deleted', '=', 0)
                                        ->whereNull('contracts.deleted_at')
                                        ->count();

                                    if ($contracts > 0) {
                                        EmailQueueService::sendEmail($data_physician);
                                    }
                                }
                            }
                            Agreement::delete_files(storage_path("signatures_" . $last_invoice_no));
                        } catch (Exception $e) {
                            Log::info('Message: ' . $e->getMessage());
                        }
                    }

                    Hospital::postHospitalsContractTotalSpendAndPaid($invoice_result["hospital"]->id);
                    UpdatePendingPaymentCount::dispatch($invoice_result["hospital"]->id);
                }
            }

            return Response::json([
                "status" => true,
                "data" => [],
                "errorCount" => 0
            ]);
        }
    }

    public function checkAgreementApproval($agreement_id)
    {
        /* get Approval process status
            */
        $result = array();
        $approvalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $agreement_id)
            ->where('is_deleted', '=', '0')
            ->where('contract_id', "=", '0')
            ->orderBy('level')->get();
        $agreement = Agreement::findOrFail($agreement_id)->toArray();
        $agreemnet_approval_info = $approvalManagerInfo->toArray();
        $result_json = json_encode($result);
        return ['agreement' => $agreement, 'approvalManagerInfo' => $agreemnet_approval_info, 'agreement_start_date' => date('m/d/Y', strtotime($agreement['start_date'])), 'agreement_end_date' => date('m/d/Y', strtotime($agreement['end_date'])), 'agreement_valid_upto_date' => date('m/d/Y', strtotime($agreement['valid_upto']))];
    }

    public function getCreateContract($id)
    {
        $agreement = Agreement::findOrFail($id);
        $hospital = Hospital::findOrFail($agreement->hospital_id);

        $users = User::getHospitalUsersByHospitalId($agreement->hospital_id);
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[0] = "NA";

        $categories = ActionCategories::getCategoriesByPaymentType(Request::input('payment_type'));
        $categories_except_rehab = ActionCategories::getCategoriesByPaymentType(0); //This is for all categories other than rehab payment type
        $categories_for_rehab = ActionCategories::getCategoriesByPaymentType(PaymentType::REHAB); //This is for all rehab categories only.

        $actions = DB::table('actions')->select('actions.*')
            ->join('action_hospitals', 'action_hospitals.action_id', '=', 'actions.id')
            ->whereIn('action_hospitals.hospital_id', [0, $agreement->hospital_id])
            ->where('action_hospitals.is_active', '=', 1)
            ->distinct()
            ->get();

        $perDiemActions = Action::where('payment_type_id', '=', PaymentType::PER_DIEM)
            ->where('action_type_id', '!=', 5)
            ->orderBy('actions.sort_order', 'asc')
            ->get();

        $perDiemUncompensatedActions = Action::where('payment_type_id', '=', PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
            ->where('action_type_id', '!=', 5)
            ->orderBy('actions.sort_order', 'asc')
            ->get();

        //keep uncompesated activity checked on create page
        foreach ($perDiemUncompensatedActions as $uncompensatedactions) {
            $uncompensatedactions->checked = true;
        }

        $hours_calculations = range(0, 24);
        unset($hours_calculations[0]);

        $selected_agreement = Agreement::getActiveAgreementsById($agreement->id);

        $hospitals_physicians = Physician::getAllPhysiciansForHospital($agreement->hospital_id);

        if (count($hospitals_physicians) > 0) {
            $data = [
                'contractTypes' => options(ContractType::all(), 'id', 'name'),
                'paymentTypes' => options(PaymentType::all()->except(9), 'id', 'name'),
                'contractNames' => ContractName::options(Request::input('payment_type', 1)),
                'agreements' => $selected_agreement,
                'agreement_pmt_frequency' => Agreement::getAgreementsPmtFrequency($agreement->hospital_id),
                'users' => $users,
                'categories' => $categories,
                'actions' => $actions,
                'per_diem_actions' => $perDiemActions,
                'per_diem_uncompensated_action' => $perDiemUncompensatedActions,
                'hours_calculations' => $hours_calculations,
                'categories_count' => count($categories),
                'categories_except_rehab' => $categories_except_rehab,
                'categories_for_rehab' => $categories_for_rehab,
                'hospitals_physicians' => $hospitals_physicians,
                'agreement' => $agreement,
                'hospital' => $hospital,
                'physician' => Physician::findOrFail($hospitals_physicians[0]->id), //This is not needed from this screen but sending it just to use existing create contract blade
                'practice' => Practice::findOrFail($hospitals_physicians[0]->practice_id), //This is not needed from this screen but sending it just to use existing create contract blade
                'supervisionTypes' => options(PhysicianType::all(), 'id', 'type')
            ];
            if (Request::input('payment_type') == 4) {
                $data['contractTypes'] = ContractType::psaOptions();
            }

            if (Request::ajax()) {
                return Response::json($data);
            }

            return View::make('physicians/create_contract')->with($data);
        } else {
            return Redirect::back()->with([
                'error' => "No physician available in hospital to create contract."
            ])->withInput();
        }
    }

    public function postCreateContract($id)
    {


        $result = Contract::createContract(0, 0, 'agreement_screen');
        return $result;


    }
}
