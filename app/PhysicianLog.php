<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTime;
use Request;
use Auth;
use Mail;
use Log;
use DateTimeZone;
use StdClass;
use Response;
use Lang;
use Edujugon\PushNotification\PushNotification;
use App\Services\NotificationService;
use Session;
use OneSignal;
use App\customClasses\PaymentFrequencyFactoryClass;
use App\Jobs\UpdateLogCounts;
use App\Jobs\UpdateTimeToApprove;
use App\HospitalOverrideMandateDetails;
use App\HospitalTimeStampEntry;
use App\Jobs\UpdatePaymentStatusDashboard;
use App\Jobs\UpdatePendingPaymentCount;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\Amount_paid;
use function App\Start\hospital_report_path;

class PhysicianLog extends Model
{
    use SoftDeletes;

    const ENTERED_BY_PHYSICIAN = 1;
    const ENTERED_BY_USER = 0;
    const SHIFT_AM = 1;
    const SHIFT_PM = 2;
    const STATUS_FAILURE = 0;

    //physician type used in physician logs
    const STATUS_SUCCESS = 1;
    protected $table = 'physician_logs';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function datesOptions($dates)
    {
        $results = array();
        $start_dates = array();
        $end_dates = array();
        $start = date("Y-m-01", strtotime($dates['start']));
        $end = date("Y-m-t", strtotime($dates['end']));
        $term = months($start, $end);
        $start_date = with(new DateTime($start))->setTime(0, 0, 0);
        $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
        for ($i = 0; $i < $term; $i++) {
            $month_data = new StdClass;
            $month_data->number = $i + 1;
            $month_data->start_date = $start_date->format('m/d/Y');
            $month_data->startdate = $start_date->format('Y-m-d');
            $month_data->end_date = $end_date->format('m/d/Y');
            $month_data->enddate = $end_date->format('Y-m-d');

            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);


            $start_dates["{$month_data->startdate}"] = "{$month_data->number}: {$month_data->start_date}";
            $end_dates["{$month_data->enddate}"] = "{$month_data->number}: {$month_data->end_date}";
        }
        $results = ["start_dates" => $start_dates, "end_dates" => $end_dates];
        return $results;
    }

    public static function penddingApprovalForPhysician($physician_id)
    {
        $check = self::where('approval_date', '=', '0000-00-00')->where('signature', '=', '')->where('physician_id', '=', $physician_id)->get();
        if (count($check) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getApprovedLogsMonths($contract, $start_date, $prior_month_end_date)
    {
        $results = $contract->logs()
            ->select(DB::raw('YEAR(physician_logs.date) year, MONTH(physician_logs.date) month'))
            ->where("physician_logs.created_at", ">=", $start_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where(function ($query) {
                $query->where('physician_logs.approval_date', '!=', '0000-00-00')
                    ->orWhere('physician_logs.signature', '!=', '');
            })
            ->distinct()
            ->get();

        return $results;
    }

    //call-coverage-duration  by 1254

    public static function penddingApprovalForContract($contract_id)
    {
        $check = self::where('approval_date', '=', '0000-00-00')->where('signature', '=', '')->where('contract_id', '=', $contract_id)->get();
        if (count($check) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function penddingPhysicianApprovalForContract($contract_id)
    {
        $check = self::where('approval_date', '=', '0000-00-00')->where('signature', '=', '')->where('contract_id', '=', $contract_id)->pluck('id');
        if (count($check) > 0) {
            $checkPhysicians = LogApprovalHistory::whereIn("log_id", $check)->where('role', '=', LogApproval::physician)->get();
            if (count($checkPhysicians) == count($check)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function postUnapproveLogs($id = 0, $practice_id = 0, $contract_id, $physician_id = 0, $period, $performed_by, $start_date = '', $end_date = '', $reason_id = 0, $custome_reason = '')
    {
        $contract = Contract::findOrFail($contract_id);
        $period = $period;
        $hospital_id = Hospital::findOrFail($contract
            ->agreement
            ->hospital
            ->id)->id;
        $performed_by = $performed_by;
        $data = (array)[];
        $now = date('mdYhis');
        $timestamp = date('Y-m-d H:i:s');
        $report_path = hospital_report_path(Hospital::findOrFail($hospital_id));
        $report_path = $report_path . '/unapprovedLogs';
        //get periods
        $periods = $contract->getContractPeriods($contract->id);
        //get start date and end date
        if ($start_date == '' && $start_date == '') {
            $start_date = $periods->months[$period]->start_date;
            $start_date_exploded = explode('/', $start_date);
            $start_date_sql = $start_date_exploded[2] . '-' . $start_date_exploded[0] . '-' . $start_date_exploded[1];
            $end_date = $periods->months[$period]->end_date;
            $end_date_exploded = explode('/', $end_date);
            $end_date_sql = $end_date_exploded[2] . '-' . $end_date_exploded[0] . '-' . $end_date_exploded[1];
        } else {
            $start_date_sql = $start_date;
            $end_date_sql = $end_date;
        }
        //check for payments, if yes, return error
        $amount_paid = Amount_paid::where('contract_id', '=', $contract->id)
            ->where('start_date', '=', $start_date_sql)
            ->where('end_date', '=', $end_date_sql)
            ->get();
        if ($amount_paid->count() > 0) {
            //physician to multiple hospital by 1254
            $data["status"] = 'payment_error';
            $data["contract_id"] = $contract->id;
            $data["practice_id"] = $practice_id;
            $data["message"] = "Payment Error.";
            return $data;
            //   return Redirect::route('contracts.edit', [$contract->id,$practice_id])
            //     ->with(['error' => Lang::get('contracts.unapprove_error_payments')]);
        }
        //get physician_logs
        // $physician_logs = PhysicianLog::where('physician_id','=',$contract->physician_id)
        // ->where('contract_id','=',$contract->id)
        // ->where('date','>=',$start_date_sql)
        // ->where('date','<=',$end_date_sql)
        // ->whereNull('deleted_at')
        // ->get();

        $physician_logs = PhysicianLog::select(
            DB::raw("physicians.email, CONCAT(physicians.first_name , ' ' , physicians.last_name ) as physician_name"),
            DB::raw("contract_names.name as contract_name"),
            DB::raw("actions.name as action_name"),
            DB::raw("physician_logs.*"))
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id');
        if ($physician_id != 0) {
            $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $physician_id);
        }
        $physician_logs = $physician_logs->where('physician_logs.contract_id', '=', $contract->id)
            ->where('physician_logs.date', '>=', $start_date_sql)
            ->where('physician_logs.date', '<=', $end_date_sql)
            ->whereNull('physician_logs.deleted_at')
            ->get();

        if ($physician_logs->count() <= 0) {
            //physician to multiple hospital by 1254
            $data["status"] = 'log_error';
            $data["contract_id"] = $contract->id;
            $data["practice_id"] = $practice_id;
            $data["message"] = "Physician Log Error.";
            return $data;

            // return Redirect::route('contracts.edit', [$contract->id,$practice_id])
            //     ->with(['error' => Lang::get('contracts.unapprove_error_no_logs')]);
        }

        //create files for logs and write headers
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }
        $physician_logs_filename_short = "physician_logs_" . $now . ".txt";
        $physician_logs_filename = $report_path . '/' . $physician_logs_filename_short;
        $physician_logs_file = fopen($physician_logs_filename, "w");
        $physician_logs_header = "performed_by,id,approval_date,signature,approved_by,approving_user_type,signatureTimeZone,next_approver_level,next_approver_user" . "\n";
        fwrite($physician_logs_file, $physician_logs_header);
        $log_approval_filename_short = "log_approval_" . $now . ".txt";
        $log_approval_filename = $report_path . '/' . $log_approval_filename_short;
        $log_approval_file = fopen($log_approval_filename, "w");
        $log_approval_header = "performed_by,id,log_id,user_id,role,approval_managers_level,approval_date,signature_id,reason_for_reject,approval_status,created_at,updated_at,signatureTimeZone" . "\n";
        fwrite($log_approval_file, $log_approval_header);
        $log_approval_history_filename_short = "log_approval_history_" . $now . ".txt";
        $log_approval_history_filename = $report_path . '/' . $log_approval_history_filename_short;
        $log_approval_history_file = fopen($log_approval_history_filename, "w");
        $log_approval_history_header = "performed_by,id,log_id,user_id,role,approval_managers_level,approval_date,signature_id,reason_for_reject,approval_status,created_at,updated_at" . "\n";
        fwrite($log_approval_history_file, $log_approval_history_header);


        $physician_email = '';
        $email_data = [];
        $temp_log_arr = [];
        $unapproved_log_ids = [];
        foreach ($physician_logs as $physician_log) {
            $temp_arr = [];
            $physician_details = Physician::where('id', '=', $physician_log->physician_id)
                ->get();

            $email_data['email'] = $physician_log->email;
            $email_data['name'] = $physician_log->physician_name;

            $temp_arr['email'] = $physician_log->email;
            $temp_arr['name'] = $physician_log->physician_name;
            $temp_arr['contract_name'] = $physician_log->contract_name;
            $temp_arr['log_date'] = $physician_log->date;
            $temp_arr['action'] = $physician_log->action_name;
            $temp_arr['duration'] = $physician_log->duration;
            $temp_arr['details'] = $physician_log->details;
            $temp_arr['reason'] = $custome_reason;
            $unapproved_name = User::select(DB::raw("CONCAT(first_name , ' ' , last_name ) as rejected_user_name"))->where('id', '=', $performed_by)->first();
            $temp_arr['rejected_user_name'] = $unapproved_name->rejected_user_name;

            array_push($temp_log_arr, $temp_arr);
            array_push($unapproved_log_ids, $physician_log->id);
            $email_data['logArray'] = $temp_log_arr;

            //get log_approval and delete
            $log_approvals = LogApproval::where('log_id', '=', $physician_log->id)
                ->get();

            if ($log_approvals->count() > 0) {
                //write log of what was deleted
                foreach ($log_approvals as $log_approval) {
                    //write log of what was deleted
                    $log_approval_line = "";
                    $log_approval_line = $performed_by . ',' . $log_approval->id . ',' . $log_approval->log_id . ',' . $log_approval->user_id . ',' . $log_approval->role . ',' . $log_approval->approval_managers_level . ',' . $log_approval->approval_date . ',' . $log_approval->signature_id . ',' . $log_approval->reason_for_reject . ',' . $log_approval->approval_status . ',' . $log_approval->created_at . ',' . $log_approval->updated_at . ',' . $log_approval->signatureTimeZone . "\n";
                    fwrite($log_approval_file, $log_approval_line);

                    $log_unapprove_history = new LogUnapprovalHistory();
                    $log_unapprove_history->log_id = $log_approval->log_id;
                    $log_unapprove_history->user_id = $log_approval->user_id;
                    $log_unapprove_history->role = $log_approval->role;
                    $log_unapprove_history->approval_date = $log_approval->approval_date;
                    $log_unapprove_history->signature_id = $log_approval->signature_id;
                    $log_unapprove_history->reason_for_reject = $log_approval->reason_for_reject;
                    $log_unapprove_history->approval_status = $log_approval->approval_status;

                    if ($reason_id == '-1' && $custome_reason != '') {
                        $reason_id = DB::table('rejected_log_reasons')->insertGetId(['reason' => $custome_reason, "is_custom_reason" => 1]);
                    }

                    $log_unapprove_history->reason_for_unapprove = (int)$reason_id;
                    $log_unapprove_history->unapproved_by = $performed_by;
                    $log_unapprove_history->save();

                    $log_approval->delete();
                }
            }
            //get log_approval_history and delete
            $log_approval_historys = LogApprovalHistory::where('log_id', '=', $physician_log->id)
                ->get();
            if ($log_approval_historys->count() > 0) {
                foreach ($log_approval_historys as $log_approval_history) {
                    //write log of what was deleted
                    $log_approval_history_line = "";
                    $log_approval_history_line = $performed_by . ',' . $log_approval_history->id . ',' . $log_approval_history->log_id . ',' . $log_approval_history->user_id . ',' . $log_approval_history->role . ',' . $log_approval_history->approval_managers_level . ',' . $log_approval_history->approval_date . ',' . $log_approval_history->signature_id . ',' . $log_approval_history->reason_for_reject . ',' . $log_approval_history->approval_status . ',' . $log_approval_history->created_at . ',' . $log_approval_history->updated_at . "\n";
                    fwrite($log_approval_history_file, $log_approval_history_line);
                    $log_approval_history->delete();
                }
            }

            //write log of what physician_log looked like before change
            $physician_logs_line = "";
            $physician_logs_line = $performed_by . ',' . $physician_log->id . ',' . $physician_log->approval_date . ',' . $physician_log->signature . ',' . $physician_log->approved_by . ',' . $physician_log->approving_user_type . ',' . $physician_log->signatureTimeZone . ',' . $physician_log->next_approver_level . ',' . $physician_log->next_approver_user . "\n";
            fwrite($physician_logs_file, $physician_logs_line);
            $physician_log->approval_date = '0000-00-00';
            $physician_log->signature = 0;
            $physician_log->approved_by = 0;
            $physician_log->approving_user_type = 0;
            $physician_log->signatureTimeZone = '';
            $physician_log->next_approver_level = 0;
            $physician_log->next_approver_user = 0;
            //update physician_logs
            $physician_log->save();

            UpdatePaymentStatusDashboard::dispatch($physician_log->physician_id, $physician_log->practice_id, $contract->id, $contract->contract_name_id, $hospital_id, $contract->agreement_id, $start_date);
        }
        //closse log files
        fclose($log_approval_file);
        fclose($log_approval_history_file);
        fclose($physician_logs_file);

        //physician to multiple hospital by 1254
        $data["status"] = 'success';
        $data["contract_id"] = $contract->id;
        $data["practice_id"] = $practice_id;
        $data['physican_email'] = $physician_email;
        $data["message"] = "Successfully unaprroved logs.";

        $email_data['type'] = EmailSetup::LOG_UNAPPROVAL;
        $email_data['with'] = [
            'logArray' => $temp_log_arr
        ];

        if ($start_date != '' && $start_date != '') {
            try {
                EmailQueueService::sendEmail($email_data);
            } catch (Exception $e) {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => $e->getMessage()
                ]);
            }

            $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician_logs[0]['physician_id'])->first();
            if ($device_token) {
                $deviceToken = $device_token->device_token;
                $deviceType = $device_token->device_type;
            } else {
                $deviceToken = "";
                $deviceType = "";
            }

            if ($deviceToken != "" && $deviceType != "") {

                // OneSignal push notification code is added here by akash
                $push_msg = 'Your time logs associated with ' . $temp_log_arr[0]['contract_name'] . ' have been rejected. All entries have been unapproved, please edit logs & reapprove.';
                $notification_for = 'UNAPPROVE';
                // Log::Info('UNAPPROVE');
                $message = [
                    'content-available' => 1,
                    'sound' => 'example.aiff',

                    'actionLocKey' => 'Action button title!',
                    'action_flag' => 2,
                    'log_count' => 10,
                    'locKey' => 'localized key',
                    'locArgs' => array(
                        'localized args',
                        'localized args',
                    ),
                    'launchImage' => 'image.jpg',
                    'message' => 'Your time logs associated with ' . $temp_log_arr[0]['contract_name'] . ' have been rejected. All entries have been unapproved, please edit logs & reapprove.'
                ];

                try {
                    if ($deviceType == PhysicianDeviceToken::iOS) {
                        $title = 'Your time logs associated with ' . $temp_log_arr[0]['contract_name'] . ' have been rejected';
                        $body = 'Your time logs associated with ' . $temp_log_arr[0]['contract_name'] . ' have been rejected. All entries have been unapproved, please edit logs & reapprove.';

                        $result = NotificationService::sendPushNotificationForIOS($deviceToken, $title, $body);

                    } elseif ($deviceType == PhysicianDeviceToken::Android) {
                        $result = NotificationService::sendPushNotificationForAndroid($deviceToken, $message);
                    }
                    $result = NotificationService::sendOneSignalPushNotification($deviceToken, $push_msg, $notification_for);
                } catch (Exception $e) {
                    Log::info("error", array($e));
                }
            }
        }
        if ($hospital_id != 0) {
            UpdatePendingPaymentCount::dispatch($hospital_id);
        }
        return $data;
        // return Redirect::route('contracts.edit', [$contract->id,$practice_id])
        //     ->with(['success' => Lang::get('contracts.unapprove_success')]);
    }

    /**
     * This function is used for updating the duration column value to log_hours column in physician_lof table.
     */
    public static function copyDurationValueToLogHours()
    {
        ini_set('max_execution_time', 6000);
        $get_duration_arr = self::select('id', 'duration')->get();
        if ($get_duration_arr) {
            foreach ($get_duration_arr as $log_duration_obj) {
                self::where('id', '=', $log_duration_obj->id)->where('log_hours', '=', 0.0)->update(['log_hours' => $log_duration_obj->duration]);
                DB::table('physician_log_history')->where('physician_log_id', $log_duration_obj->id)->where('log_hours', '=', 0.0)->update(
                    array('log_hours' => $log_duration_obj->duration));
            }
        }
        // Log::Info('test', array($get_duration_arr));
        // Hospital::whereIn('id',$get_hospital_ids_for_region_life_point)
        // ->update(['invoice_type' => 1]);
        return 1;
    }

    public static function fetch_log_rejection_overall_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);
        $return_data = self::fetch_log_rejection_rate($user_id, $facility);

        $results = HospitalLog::get_hospital_logs_for_all_hospitals();
        $rejection_rate = $results['total_logs'] == 0 ? 0 : (($results['rejected_logs'] / $results['total_logs']) * 100);
        $result = array(
            "id" => 101,
            "name" => "Overall DYNAFIOS Average",
            "total_rate" => number_format($rejection_rate, 2)
        );

        array_push($return_data, $result);
        sort($return_data, SORT_REGULAR);

        return $return_data;
    }

    public static function fetch_log_rejection_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_hospital_logs($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_hospital_logs($hospital_list);
        }

        $rejection_rate = $results['total_logs'] == 0 ? 0 : (($results['rejected_logs'] / $results['total_logs']) * 100);

        $return_data[] = [
            "id" => 102,
            "name" => "Organization Average",
            "total_rate" => number_format($rejection_rate, 2)
        ];

        return $return_data;
    }

    public static function fetch_rejection_by_physician_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);
        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_physicians_logs($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_physicians_logs($hospital_list);
        }

        $results = collect($results)->where('rejected_logs', '>', 0)->toArray();
        $return_data = array();
        foreach ($results as $result) {
            $hospital = Hospital::findOrFail($result['hospital_id']);
            $physician = Physician::findOrFail($result['physician_id']);

            $rejection_rate = $result['total_logs'] == 0 ? 0 : (($result['rejected_logs'] / $result['total_logs']) * 100);

            $return_data[] = [
                "physician_id" => $physician->id,
                "physician_name" => $physician->first_name . ' ' . $physician->last_name,
                "rejection_count" => $result['rejected_logs'],
                "logs_count" => $result['total_logs'],
                "rate" => number_format($rejection_rate, 2)
            ];
        }

        $data = collect($return_data)->sortBy('rate')->reverse()->toArray();
        $data = array_slice($data, 0, 7);

        return $data;
    }

    public static function fetch_rejection_by_contract_type_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_contracts_type_logs($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_contracts_type_logs($hospital_list);
        }

        $return_data = array();

        foreach ($results as $result) {
            $contract = Contract::findOrFail($result->contract_id);

            $contract_type = Contract::select('contract_types.id as contract_type_id', 'contract_types.name as contract_type_name')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                ->first();

            $collection = collect($return_data);
            $check_exist = $collection->contains('contract_type_id', $contract_type['contract_type_id']);

            if ($check_exist) {
                $data = collect($return_data)->where('contract_type_id', $contract_type['contract_type_id'])->all();

                foreach ($data as $data) {
                    $rejection_count = $data["rejection_count"] + $result->rejected_logs;
                    $logs_count = $data["logs_count"] + $result->logs;
                    $contract_type_id = $data["contract_type_id"];

                    foreach ($return_data as $key => $value) {
                        if ($value["contract_type_id"] == $contract_type_id) {
                            unset($return_data[$key]);
                        }
                    }

                    $return_data[] = [
                        "contract_type_id" => $contract_type['contract_type_id'],
                        "contract_type_name" => $contract_type['contract_type_name'],
                        "rejection_count" => $rejection_count,
                        "logs_count" => $logs_count
                    ];
                }
            } else {
                $return_data[] = [
                    "contract_type_id" => $contract_type['contract_type_id'],
                    "contract_type_name" => $contract_type['contract_type_name'],
                    "rejection_count" => $result->rejected_logs,
                    "logs_count" => $result->logs
                ];
            }
        }

        return $return_data;
    }

    public static function fetch_rejection_by_practice_rate($user_id = 0, $facility, $practice_id, $popupdata)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_practice_logs($hospital, $practice_id);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_practice_logs($hospital_list, $practice_id);
        }

        $return_data = array();
        $results = collect($results)->where('rejected_logs', '>', 0)->toArray();

        foreach ($results as $result) {
            $hospital = Hospital::findOrFail($result['hospital_id']);
            $practice = Practice::findOrFail($result['practice_id']);

            $rejection_rate = $result['total_logs'] == 0 ? 0 : (($result['rejected_logs'] / $result['total_logs']) * 100);

            $return_data[] = [
                "practice_id" => $practice->id,
                "practice_name" => $practice->name,
                "rejection_count" => $result['rejected_logs'],
                "logs_count" => $result['total_logs'],
                "rate" => number_format($rejection_rate, 2),
                "hospital_name" => $hospital->name
            ];
        }

        $data = collect($return_data)->sortBy('rate')->reverse()->toArray();
        if ($popupdata == 0) {
            $data = array_slice($data, 0, 7);
        }
        return $data;
    }

    //Rohit Added on 15/09/2022

    public static function fetch_rejection_by_reason_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_contracts($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_contracts($hospital_list);
        }

        $return_data = array();
        $count = 0;
        $custom_reason_count = 0;
        foreach ($results as $result) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($result->agreement_id);

            $rejected_logs = PhysicianLog::select('physician_logs.id', 'rejected_log_reasons.id as reason_id', 'rejected_log_reasons.reason as reason_name', 'rejected_log_reasons.is_custom_reason as is_custom_reason')
                ->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                ->join('log_approval_history', 'log_approval_history.log_id', '=', 'log_approval.log_id')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('rejected_log_reasons', 'rejected_log_reasons.id', '=', 'log_approval_history.reason_for_reject')
                ->where('log_approval.role', '=', '1')
                ->where('log_approval.approval_status', '=', '0')
                ->where('log_approval_history.reason_for_reject', '!=', 0)
                ->where('contracts.id', '=', $result->contract_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull('physician_logs.deleted_at')
                ->distinct()
                ->get();


            foreach ($rejected_logs as $rejected_log) {
                if ($rejected_log->is_custom_reason == 0) {
                    $count = 1;
                    $collection = collect($return_data);
                    $check_exist = $collection->contains('reason_id', $rejected_log->reason_id);

                    if ($check_exist) {
                        $data = collect($return_data)->where('reason_id', $rejected_log->reason_id)->all();

                        foreach ($data as $data) {
                            $rejection_count = $data["rejection_count"] + $count;
                            $logs_count = $data["logs_count"] + $count;
                            $reason_id = $data["reason_id"];

                            foreach ($return_data as $key => $value) {
                                if ($value["reason_id"] == $reason_id) {
                                    unset($return_data[$key]);
                                }
                            }

                            $return_data[] = [
                                "reason_id" => $rejected_log->reason_id,
                                "reason_name" => $rejected_log->reason_name,
                                "rejection_count" => $rejection_count,
                                "logs_count" => $logs_count
                            ];
                        }
                    } else {
                        $return_data[] = [
                            "reason_id" => $rejected_log->reason_id,
                            "reason_name" => $rejected_log->reason_name,
                            "rejection_count" => $count,
                            "logs_count" => $count
                        ];
                    }
                } else {
                    $custom_reason_count++;

                    $return_data[] = [
                        "reason_id" => 1001,
                        "reason_name" => "Custom reason",
                        "rejection_count" => $custom_reason_count,
                        "logs_count" => $custom_reason_count
                    ];
                }
            }
        }

        return $return_data;
    }

    public static function fetch_rejection_by_approver_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_contracts($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_contracts($hospital_list);
        }

        $return_data = array();
        $count = 0;
        foreach ($results as $result) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($result->agreement_id);

            $rejected_logs = PhysicianLog::select('physician_logs.id', 'log_approval_history.user_id as user_id', 'users.first_name as name')
                ->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                ->join('log_approval_history', 'log_approval_history.log_id', '=', 'log_approval.log_id')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('rejected_log_reasons', 'rejected_log_reasons.id', '=', 'log_approval_history.reason_for_reject')
                ->join('users', 'users.id', '=', 'log_approval_history.user_id')
                ->where('log_approval.role', '=', '1')
                ->where('log_approval.approval_status', '=', '0')
                ->where('log_approval_history.reason_for_reject', '!=', 0)
                ->where('contracts.id', '=', $result->contract_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull('physician_logs.deleted_at')
                ->distinct()
                ->get();

            foreach ($rejected_logs as $rejected_log) {
                $count = 1;
                $collection = collect($return_data);
                $check_exist = $collection->contains('approver_id', $rejected_log->user_id);

                if ($check_exist) {
                    $data = collect($return_data)->where('approver_id', $rejected_log->user_id)->all();

                    foreach ($data as $data) {
                        $rejection_count = $data["rejection_count"] + $count;
                        $logs_count = $data["logs_count"] + $count;
                        $approver_id = $data["approver_id"];

                        foreach ($return_data as $key => $value) {
                            if ($value["approver_id"] == $approver_id) {
                                unset($return_data[$key]);
                            }
                        }

                        $return_data[] = [
                            "approver_id" => $rejected_log->user_id,
                            "approver_name" => $rejected_log->name,
                            "rejection_count" => $rejection_count,
                            "logs_count" => $logs_count
                        ];
                    }
                } else {
                    $return_data[] = [
                        "approver_id" => $rejected_log->user_id,
                        "approver_name" => $rejected_log->name,
                        "rejection_count" => $count,
                        "logs_count" => $count
                    ];
                }
            }
        }

        return $return_data;
    }

    public static function fetch_average_duration_of_approval_time($user_id = 0, $facility, $contract_type_id = 0)
    {
        ini_set('max_execution_time', 6000);
        $query = ContractType::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        if ($contract_type_id != 0) {
            $query = $query->where("contracts.contract_type_id", "=", $contract_type_id);
        }
        $contract_types = $query->get();

        $hospital_list = array();
        if ($facility == 0) {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
        } else {
            $hospital_list[] = $facility;
        }


        $sorted_array = array();
        $return_data = array();
        $total_rate = 0;
        $index = 0;

        foreach ($contract_types as $contract_type) {
            $contracts = ContractType::getContractsType($contract_type->id, $user_id, $hospital_list);
            // log::info('$contract_type', array($contract_type));
            if (count($contracts) > 0) {
                $total_days = 0;
                $log_count = 0;
                foreach ($contracts as $contract) {
                    // log::info('$contract', array($contract));
                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
                    $month = "00";
                    $year = "0000";
                    $approval_date = '0000-00-00';

                    // Below code is added for updating the time_to_approve columns in physician_logs table.
                    $agreement_data = Agreement::getAgreementData($contract->agreement_id);
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
                    // log::info('$res_pay_frequency', array($res_pay_frequency));

                    $period_dates = $res_pay_frequency['date_range_with_start_end_date'];

                    // $log_count = 0;
                    foreach ($period_dates as $dates_obj) {
                        // log::info('$startEndDatesForYear', array($startEndDatesForYear['year_start_date']->format('Y-m-d')));
                        // log::info('$endEndDatesForYear', array($startEndDatesForYear['year_end_date']->format('Y-m-d')));
                        // log::info('$start', array($dates_obj['start_date']));
                        // log::info('$end', array($dates_obj['end_date']));
                        // log::info('$contract->id', array($contract->id));
                        if (strtotime($dates_obj['start_date']) >= strtotime($startEndDatesForYear['year_start_date']->format('Y-m-d')) && strtotime($dates_obj['end_date']) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d'))) {
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
                                // log::info('$time', array($days_sume));

                                $log_count = $log_count + 1;
                                $total_days += $days_sume;
                            }
                        }
                    }

                    if ($contract_type_id != 0 && $total_days > 0) {
                        $return_data[] = [
                            "hospital_name" => $contract->hospital_name,
                            "physician_name" => $contract->physician_name,
                            "contract_name" => $contract->contract_name,
                            "agreement_start_date" => $startEndDatesForYear['year_start_date']->format('m/d/Y'),
                            "agreement_end_date" => $startEndDatesForYear['year_end_date']->format('m/d/Y'),
                            "rate" => number_format($log_count > 0 ? ($total_days / $log_count) : 0, 2),
                        ];
                        $total_days = 0;
                        $log_count = 0;
                    }

                    // log::info('$total_days', array($total_days));
                    // log::info('$log_count', array($log_count));
                }
                if ($total_days > 0) {
                    $return_data[] = [
                        "contract_type_id" => $contract_type->id,
                        "contract_type_name" => $contract_type->name,
                        "active_contract_count" => count($contracts),
                        // "contract_average_days" => number_format(($total_days / $count), 2),
                        "contract_average_days" => number_format($log_count > 0 ? ($total_days / $log_count) : 0, 2),
                        "index" => $index
                    ];
                }
                $index++;
            }
        }
        $data = collect($return_data)->sortBy('contract_average_days')->reverse()->toArray();
        // $data = array_slice($data, 0, 7);
        return $data;
    }

    public static function fetch_average_duration_of_time_between_approve_logs($user_id = 0, $facility, $contract_type_id = 0)
    {
        ini_set('max_execution_time', 6000);
        $query = ContractType::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        if ($contract_type_id != 0) {
            $query = $query->where("contracts.contract_type_id", "=", $contract_type_id);
        }
        $contract_types = $query->get();

        $hospital_list = array();
        if ($facility == 0) {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
        } else {
            $hospital_list[] = $facility;
        }

        $sorted_array = array();
        $return_data = array();
        $total_rate = 0;
        $index = 0;
        foreach ($contract_types as $contract_type) {
            $contracts = ContractType::getContractsType($contract_type->id, $user_id, $hospital_list);

            if (count($contracts) > 0) {
                $total_days = 0;
                $approval_date = '0000-00-00';
                $total_rate = 0;
                $total_count = 0;
                $total_logs = 0;

                foreach ($contracts as $contract) {

                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

                    $query = PhysicianLog::select("physician_logs.*")
                        ->whereNull('physician_logs.deleted_at')
                        ->where('physician_logs.contract_id', '=', $contract->id)
                        ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                        ->orderBy('physician_logs.date', 'asc')
                        ->where('physician_logs.time_to_approve_by_physician', '>', 0);
                    $logs = $query->sum('physician_logs.time_to_approve_by_physician');

                    $count = $query->get()->count();

                    if ($count > 0) {
                        $total_days += $logs;
                        $total_count += $count;
                    }


                    if ($contract_type_id != 0 && $count > 0) {
                        $return_data[] = [
                            "hospital_name" => $contract->hospital_name,
                            "physician_name" => $contract->physician_name,
                            "contract_name" => $contract->contract_name,
                            "agreement_start_date" => $startEndDatesForYear['year_start_date']->format("m/d/Y"),
                            "agreement_end_date" => $startEndDatesForYear['year_end_date']->format("m/d/Y"),
                            // "rate" => number_format($total_days, 2),
                            "rate" => number_format(($logs / $count), 2),
                        ];
                        $total_days = 0;
                    }
                }

                if ($total_days > 0) {
                    $return_data[] = [
                        "contract_type_id" => $contract_type->id,
                        "contract_type_name" => $contract_type->name,
                        "active_contract_count" => count($contracts),
                        "contract_average_days" => number_format(($total_days / $total_count), 2),
                        // "contract_average_days" => number_format($total_days, 2),
                        "index" => $index
                    ];
                }
                $index++;
            }
        }
        $data = collect($return_data)->sortBy('contract_average_days')->reverse()->toArray();
        // $data = array_slice($data, 0, 7);
        return $data;
    }

    public static function fetch_log_rejection($user_id, $agreement_list, $contract_type)
    {

        $total_log = 0;
        $total_log_cytd = 0;
        $total_rejection_log = 0;
        $total_rejection_log_cytd = 0;
        $total_rate = 0;
        $agreementInfo = array();
        $facility = Request::input("facility");
        $hospital_list = array();
        if ($facility == 0) {
            $hospitals = Hospital::join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->pluck('hospitals.id')->toArray();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital;
                }
            }
        } else {
            $hospital_list[] = $facility;
        }
        $ratrForAll = self::fetch_log_rejection_rate($user_id, $facility);

        if (count($agreement_list) > 0) {
            foreach ($hospital_list as $hospital) {
                $hospitalDetails = Hospital::findOrFail($hospital);
                $agreementInfo = array();
                foreach ($agreement_list as $agreement) {
                    $total_log = 0;
                    $total_log_cytd = 0;
                    $total_rejection_log = 0;
                    $total_rejection_log_cytd = 0;
                    $agreementDetails = Agreement::findOrFail($agreement);
                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($agreement);
                    $physician_info = array();
                    $agreement_data = Agreement::getAgreementData($agreement);
                    $month = Request::input("agreement_{$agreement}_start_month");

                    $logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id", "contract_types.name as contract_type",
                        DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), "practices.name as practice_name")
                        ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                        // ->where("physician_logs.physician_id", $physician->id)
                        ->where("agreements.id", "=", $agreement)
                        ->where("agreements.hospital_id", "=", $hospital)
                        ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)])
                        ->whereNull("physician_logs.deleted_at");
                    if ($contract_type != 0) {
                        $logs = $logs->where("contracts.contract_type_id", "=", $contract_type);
                    }
                    $logs = $logs->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->get();

                    if (count($logs) > 0) {
                        // $rejection_logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                        //     // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                        //     ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                        //     ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                        //     ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        //     ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        //     ->where("log_approval.role", "=", "1")
                        //     ->where("log_approval.approval_status", "=", "0")
                        //     // ->where("log_approval_history.approval_status", "=","0")
                        //     //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                        //     ->where("agreements.id", "=", $agreement)
                        //     ->where("agreements.hospital_id", "=", $hospital)
                        //     ->whereNull("physician_logs.deleted_at")
                        //     ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)]);
                        // if($contract_type != 0){
                        //     $rejection_logs = $rejection_logs->where("contracts.contract_type_id", "=", $contract_type);
                        // }
                        // $rejection_logs = $rejection_logs->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->pluck("count", "physician_id")->toArray();

                        $logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                            // ->where("physician_logs.physician_id", $physician->id)
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                            ->whereNull("physician_logs.deleted_at");
                        if ($contract_type != 0) {
                            $logs_cytd = $logs_cytd->where("contracts.contract_type_id", "=", $contract_type);
                        }
                        $logs_cytd = $logs_cytd->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->pluck("count", "physician_id")->toArray();

                        // $rejection_logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                        //     // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                        //     ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                        //     ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                        //     ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        //     ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        //     ->where("log_approval.role", "=", "1")
                        //     ->where("log_approval.approval_status", "=", "0")
                        //     // ->where("log_approval_history.approval_status", "=","0")
                        //     //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                        //     ->where("agreements.id", "=", $agreement)
                        //     ->where("agreements.hospital_id", "=", $hospital)
                        //     ->whereNull("physician_logs.deleted_at")
                        //     ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))]);
                        // if($contract_type != 0){
                        //     $rejection_logs_cytd = $rejection_logs_cytd->where("contracts.contract_type_id", "=", $contract_type);
                        // }
                        // $rejection_logs_cytd = $rejection_logs_cytd->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->pluck("count", "physician_id")->toArray();

                        foreach ($logs as $logDetails) {

                            $rejection_logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->where("log_approval.role", "=", "1")
                                ->where("log_approval.approval_status", "=", "0")
                                ->where("agreements.id", "=", $agreement)
                                ->where("contracts.contract_type_id", "=", $logDetails->contract_type_id)
                                ->where("agreements.hospital_id", "=", $hospital)
                                ->whereNull("physician_logs.deleted_at")
                                ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)]);

                            $rejection_logs = $rejection_logs->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->pluck("count", "physician_id")->toArray();

                            $rejection_logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                                ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->where("log_approval.role", "=", "1")
                                ->where("log_approval.approval_status", "=", "0")
                                ->where("agreements.id", "=", $agreement)
                                ->where("contracts.contract_type_id", "=", $logDetails->contract_type_id)
                                ->where("agreements.hospital_id", "=", $hospital)
                                ->whereNull("physician_logs.deleted_at")
                                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))]);

                            $rejection_logs_cytd = $rejection_logs_cytd->groupBy("physician_logs.physician_id", "contracts.contract_type_id")->pluck("count", "physician_id")->toArray();

                            $physician_info[] = [
                                "practice" => $logDetails->practice_name,
                                "physician" => $logDetails->physician_name,
                                "contract_type" => $logDetails->contract_type,
                                "period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                                "submit_count" => $logDetails->count,
                                "reject_count" => array_key_exists($logDetails->physician_id, $rejection_logs) ? $rejection_logs[$logDetails->physician_id] : 0,
                                "rejection_percent" => array_key_exists($logDetails->physician_id, $rejection_logs) ? $rejection_logs[$logDetails->physician_id] / $logDetails->count * 100 : 0,
                                "submit_cytd" => array_key_exists($logDetails->physician_id, $logs_cytd) ? $logs_cytd[$logDetails->physician_id] : 0,
                                "reject_cytd" => array_key_exists($logDetails->physician_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->physician_id] : 0,
                                "rejection_percent_cytd" => array_key_exists($logDetails->physician_id, $rejection_logs_cytd) && array_key_exists($logDetails->physician_id, $logs_cytd) ? $rejection_logs_cytd[$logDetails->physician_id] / $logs_cytd[$logDetails->physician_id] * 100 : 0
                            ];

                            $total_log += $logDetails->count;
                            $total_rejection_log += array_key_exists($logDetails->physician_id, $rejection_logs) ? $rejection_logs[$logDetails->physician_id] : 0;

                            $total_log_cytd += array_key_exists($logDetails->physician_id, $logs_cytd) ? $logs_cytd[$logDetails->physician_id] : 0;
                            $total_rejection_log_cytd += array_key_exists($logDetails->physician_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->physician_id] : 0;
                        }
                    }
                    $agreementInfo[] = [
                        "name" => $agreementDetails->name,
                        "period" => format_date($agreementDetails->start_date) . ' - ' . format_date($agreementDetails->end_date),
                        "current_period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                        "total_logs" => $total_log,
                        "total_rejection_log" => $total_rejection_log,
                        "total_rejection_percent" => $total_log == 0 ? 0 : $total_rejection_log / $total_log * 100,
                        "total_log_cytd" => $total_log_cytd,
                        "total_rejection_log_cytd" => $total_rejection_log_cytd,
                        "total_rejection_percent_cytd" => $total_log_cytd == 0 ? 0 : $total_rejection_log_cytd / $total_log_cytd * 100,
                        "all_physician" => $ratrForAll[0]['total_rate'],
                        "benchmark" => $hospitalDetails->benchmark_rejection_percentage,
                        "physician_info" => $physician_info
                    ];
                }
                $timestamp = Request::input("current_timestamp");
                $timeZone = Request::input("current_zoneName");
                // log::info("timestamp",array($timestamp));

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
                // log::info("localtimeZone",array($localtimeZone));

                $return_data[] = [
                    "hospital" => $hospitalDetails,
                    "agreementInfo" => $agreementInfo,
                    "localtimeZone" => $localtimeZone
                ];
            }
        }

        return $return_data;
    }

//call-coverage-duration  by 1254

    public static function fetch_log_rejection_contract_type($user_id, $agreement_list, $contract_type)
    {

        $total_log = 0;
        $total_log_cytd = 0;
        $total_rejection_log = 0;
        $total_rejection_log_cytd = 0;
        $total_rate = 0;
        $agreementInfo = array();
        $facility = Request::input("facility");
        $hospital_list = array();
        if ($facility == 0) {
            $hospitals = Hospital::join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->pluck('hospitals.id')->toArray();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital;
                }
            }
        } else {
            $hospital_list[] = $facility;
        }

        if (count($agreement_list) > 0) {
            foreach ($hospital_list as $hospital) {
                $hospitalDetails = Hospital::findOrFail($hospital);
                $agreementInfo = array();
                foreach ($agreement_list as $agreement) {
                    $total_log = 0;
                    $total_log_cytd = 0;
                    $total_rejection_log = 0;
                    $total_rejection_log_cytd = 0;
                    $agreementDetails = Agreement::findOrFail($agreement);
                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($agreement);
                    $contract_type_info = array();
                    $agreement_data = Agreement::getAgreementData($agreement);
                    $month = Request::input("agreement_{$agreement}_start_month");

                    $logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id as contract_type_id", "contract_types.name as contract_type",
                        DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), "practices.name as practice_name")
                        ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                        // ->where("physician_logs.physician_id", $physician->id)
                        ->where("agreements.id", "=", $agreement)
                        ->where("agreements.hospital_id", "=", $hospital)
                        ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)])
                        ->whereNull("physician_logs.deleted_at");
                    if ($contract_type != 0) {
                        $logs = $logs->where("contracts.contract_type_id", "=", $contract_type);
                    }
                    $logs = $logs->groupBy("contracts.contract_type_id")->get();

                    if (count($logs) > 0) {
                        $rejection_logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id")
                            // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->where("log_approval.role", "=", "1")
                            ->where("log_approval.approval_status", "=", "0")
                            // ->where("log_approval_history.approval_status", "=","0")
                            //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereNull("physician_logs.deleted_at")
                            ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)]);
                        if ($contract_type != 0) {
                            $rejection_logs = $rejection_logs->where("contracts.contract_type_id", "=", $contract_type);
                        }
                        $rejection_logs = $rejection_logs->groupBy("contracts.contract_type_id")->pluck("count", "contracts.contract_type_id")->toArray();

                        $logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id as contract_type_id")
                            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                            // ->where("physician_logs.physician_id", $physician->id)
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                            ->whereNull("physician_logs.deleted_at");
                        if ($contract_type != 0) {
                            $logs_cytd = $logs_cytd->where("contracts.contract_type_id", "=", $contract_type);
                        }
                        $logs_cytd = $logs_cytd->groupBy("contracts.contract_type_id")->pluck("count", "contracts.contract_type_id")->toArray();

                        $rejection_logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.physician_id as physician_id", "contracts.contract_type_id as contract_type_id")
                            // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->where("log_approval.role", "=", "1")
                            ->where("log_approval.approval_status", "=", "0")
                            // ->where("log_approval_history.approval_status", "=","0")
                            //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereNull("physician_logs.deleted_at")
                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))]);
                        if ($contract_type != 0) {
                            $rejection_logs_cytd = $rejection_logs_cytd->where("contracts.contract_type_id", "=", $contract_type);
                        }
                        $rejection_logs_cytd = $rejection_logs_cytd->groupBy("contracts.contract_type_id")->pluck("count", "contracts.contract_type_id")->toArray();

                        foreach ($logs as $logDetails) {
                            $contract_type_info[] = [
                                "contract_type" => $logDetails->contract_type,
                                "contract_type_id" => $logDetails->contract_type_id,
                                "agreement_id" => $agreement,
                                "name" => $agreementDetails->name,
                                "period" => format_date($agreementDetails->start_date) . ' - ' . format_date($agreementDetails->end_date),
                                "current_period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                                "submit_count" => $logDetails->count,
                                "reject_count" => array_key_exists($logDetails->contract_type_id, $rejection_logs) ? $rejection_logs[$logDetails->contract_type_id] : 0,
                                "rejection_percent" => array_key_exists($logDetails->contract_type_id, $rejection_logs) ? $rejection_logs[$logDetails->contract_type_id] / $logDetails->count * 100 : 0,
                                "submit_cytd" => array_key_exists($logDetails->contract_type_id, $logs_cytd) ? $logs_cytd[$logDetails->contract_type_id] : 0,
                                "reject_cytd" => array_key_exists($logDetails->contract_type_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->contract_type_id] : 0,
                                "rejection_percent_cytd" => array_key_exists($logDetails->contract_type_id, $rejection_logs_cytd) && array_key_exists($logDetails->contract_type_id, $logs_cytd) ? $rejection_logs_cytd[$logDetails->contract_type_id] / $logs_cytd[$logDetails->contract_type_id] * 100 : 0
                            ];

                            $total_log += $logDetails->count;
                            $total_rejection_log += array_key_exists($logDetails->contract_type_id, $rejection_logs) ? $rejection_logs[$logDetails->contract_type_id] : 0;

                            $total_log_cytd += array_key_exists($logDetails->contract_type_id, $logs_cytd) ? $logs_cytd[$logDetails->contract_type_id] : 0;
                            $total_rejection_log_cytd += array_key_exists($logDetails->contract_type_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->contract_type_id] : 0;
                        }
                    }
                    $agreementInfo[] = [
                        "name" => $agreementDetails->name,
                        "period" => format_date($agreementDetails->start_date) . ' - ' . format_date($agreementDetails->end_date),
                        "current_period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                        "total_logs" => $total_log,
                        "total_rejection_log" => $total_rejection_log,
                        "total_rejection_percent" => $total_log == 0 ? 0 : $total_rejection_log / $total_log * 100,
                        "total_log_cytd" => $total_log_cytd,
                        "total_rejection_log_cytd" => $total_rejection_log_cytd,
                        "total_rejection_percent_cytd" => $total_log_cytd == 0 ? 0 : $total_rejection_log_cytd / $total_log_cytd * 100,
                        "benchmark" => $hospitalDetails->benchmark_rejection_percentage,
                        "contract_type_info" => $contract_type_info
                    ];
                }

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

                $report_data_final = [];
                foreach ($agreementInfo as $agreement_info_obj) {
                    foreach ($agreement_info_obj['contract_type_info'] as $contract_type_info_obj) {
                        $temp_data_arr = [];
                        if (array_key_exists($contract_type_info_obj['contract_type_id'], $report_data_final)) {
                            $report_data_final[$contract_type_info_obj['contract_type_id']][] = $contract_type_info_obj;
                        } else {
                            $report_data_final[$contract_type_info_obj['contract_type_id']][] = $contract_type_info_obj;
                        }
                    }
                }

                $return_data[] = [
                    "hospital" => $hospitalDetails,
                    "agreementInfo" => $report_data_final,
                    "localtimeZone" => $localtimeZone
                ];
            }
        }

        return $return_data;
    }

    //call-coverage-duration  by 1254 : new function added to get total duration for partial hour on

    public static function fetch_log_rejection_practice($user_id, $agreement_list, $contract_type)
    {

        $total_log = 0;
        $total_log_cytd = 0;
        $total_rejection_log = 0;
        $total_rejection_log_cytd = 0;
        $total_rate = 0;
        $agreementInfo = array();
        $facility = Request::input("facility");
        $hospital_list = array();
        if ($facility == 0) {
            $hospitals = Hospital::join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->pluck('hospitals.id')->toArray();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital;
                }
            }
        } else {
            $hospital_list[] = $facility;
        }

        if (count($agreement_list) > 0) {
            foreach ($hospital_list as $hospital) {
                $hospitalDetails = Hospital::findOrFail($hospital);
                $agreementInfo = array();
                foreach ($agreement_list as $agreement) {
                    $total_log = 0;
                    $total_log_cytd = 0;
                    $total_rejection_log = 0;
                    $total_rejection_log_cytd = 0;
                    $agreementDetails = Agreement::findOrFail($agreement);
                    $startEndDatesForYear = Agreement::getAgreementStartDateForYear($agreement);
                    $practice_info = array();
                    $agreement_data = Agreement::getAgreementData($agreement);
                    $month = Request::input("agreement_{$agreement}_start_month");

                    $logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.practice_id as practice_id", "contracts.contract_type_id", "contract_types.name as contract_type",
                        DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), "practices.name as practice_name")
                        ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                        // ->where("physician_logs.physician_id", $physician->id)
                        ->where("agreements.id", "=", $agreement)
                        ->where("agreements.hospital_id", "=", $hospital)
                        ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)])
                        ->whereNull("physician_logs.deleted_at");
                    if ($contract_type != 0) {
                        $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
                    }
                    $logs = $logs->groupBy("physician_logs.practice_id")->get();

                    if (count($logs) > 0) {
                        $rejection_logs = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.practice_id as practice_id", "contracts.contract_type_id")
                            // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->where("log_approval.role", "=", "1")
                            ->where("log_approval.approval_status", "=", "0")
                            // ->where("log_approval_history.approval_status", "=","0")
                            //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereNull("physician_logs.deleted_at")
                            ->whereBetween('physician_logs.date', [mysql_date($agreement_data->months[$month]->start_date), mysql_date($agreement_data->months[$month]->end_date)]);
                        if ($contract_type != 0) {
                            $rejection_logs = $rejection_logs->where('contracts.contract_type_id', '=', $contract_type);
                        }
                        $rejection_logs = $rejection_logs->groupBy("physician_logs.practice_id")->pluck("count", "physician_logs.practice_id")->toArray();

                        $logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.practice_id as practice_id", "contracts.contract_type_id")
                            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->join('practices', 'practices.id', '=', 'physician_logs.practice_id')
                            // ->where("physician_logs.physician_id", $physician->id)
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                            ->whereNull("physician_logs.deleted_at");
                        if ($contract_type != 0) {
                            $logs_cytd = $logs_cytd->where('contracts.contract_type_id', '=', $contract_type);
                        }
                        $logs_cytd = $logs_cytd->groupBy("physician_logs.practice_id")->pluck("count", "physician_logs.practice_id")->toArray();

                        $rejection_logs_cytd = PhysicianLog::select(DB::raw("COUNT(physician_logs.id) AS count"), "physician_logs.practice_id as practice_id", "contracts.contract_type_id")
                            // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                            ->where("log_approval.role", "=", "1")
                            ->where("log_approval.approval_status", "=", "0")
                            // ->where("log_approval_history.approval_status", "=","0")
                            //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                            ->where("agreements.id", "=", $agreement)
                            ->where("agreements.hospital_id", "=", $hospital)
                            ->whereNull("physician_logs.deleted_at")
                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))]);
                        if ($contract_type != 0) {
                            $rejection_logs_cytd = $rejection_logs_cytd->where('contracts.contract_type_id', '=', $contract_type);
                        }
                        $rejection_logs_cytd = $rejection_logs_cytd->groupBy("physician_logs.practice_id")->pluck("count", "physician_logs.practice_id")->toArray();

                        foreach ($logs as $logDetails) {
                            $practice_info[] = [
                                "practice" => $logDetails->practice_name,
                                "period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                                "submit_count" => $logDetails->count,
                                "reject_count" => array_key_exists($logDetails->practice_id, $rejection_logs) ? $rejection_logs[$logDetails->practice_id] : 0,
                                "rejection_percent" => array_key_exists($logDetails->practice_id, $rejection_logs) ? $rejection_logs[$logDetails->practice_id] / $logDetails->count * 100 : 0,
                                "submit_cytd" => array_key_exists($logDetails->practice_id, $logs_cytd) ? $logs_cytd[$logDetails->practice_id] : 0,
                                "reject_cytd" => array_key_exists($logDetails->practice_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->practice_id] : 0,
                                "rejection_percent_cytd" => array_key_exists($logDetails->practice_id, $rejection_logs_cytd) && array_key_exists($logDetails->practice_id, $logs_cytd) ? $rejection_logs_cytd[$logDetails->practice_id] / $logs_cytd[$logDetails->practice_id] * 100 : 0
                            ];

                            $total_log += $logDetails->count;
                            $total_rejection_log += array_key_exists($logDetails->practice_id, $rejection_logs) ? $rejection_logs[$logDetails->practice_id] : 0;

                            $total_log_cytd += array_key_exists($logDetails->practice_id, $logs_cytd) ? $logs_cytd[$logDetails->practice_id] : 0;
                            $total_rejection_log_cytd += array_key_exists($logDetails->practice_id, $rejection_logs_cytd) ? $rejection_logs_cytd[$logDetails->practice_id] : 0;
                        }
                    }
                    $agreementInfo[] = [
                        "name" => $agreementDetails->name,
                        "period" => format_date($agreementDetails->start_date) . ' - ' . format_date($agreementDetails->end_date),
                        "current_period" => $agreement_data->months[$month]->start_date . ' - ' . $agreement_data->months[$month]->end_date,
                        "total_logs" => $total_log,
                        "total_rejection_log" => $total_rejection_log,
                        "total_rejection_percent" => $total_log == 0 ? 0 : $total_rejection_log / $total_log * 100,
                        "total_log_cytd" => $total_log_cytd,
                        "total_rejection_log_cytd" => $total_rejection_log_cytd,
                        "total_rejection_percent_cytd" => $total_log_cytd == 0 ? 0 : $total_rejection_log_cytd / $total_log_cytd * 100,
                        "benchmark" => $hospitalDetails->benchmark_rejection_percentage,
                        "practice_info" => $practice_info
                    ];
                }

                $timestamp = Request::input("current_timestamp");
                $timeZone = Request::input("current_zoneName");
                //log::info("timestamp",array($timestamp));

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
                $return_data[] = [
                    "hospital" => $hospitalDetails,
                    "agreementInfo" => $agreementInfo,
                    "localtimeZone" => $localtimeZone

                ];
            }
        }

        return $return_data;
    }

    public static function fetch_compliance_rejectionby_physician($user_id = 0, $facility, $physician_id)
    {
        ini_set('max_execution_time', 6000);
        $hospital_list = array();
        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_physician_logs_for_popup($hospital, $physician_id);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $results = HospitalLog::get_physician_logs_for_popup($hospital_list, $physician_id);
        }

        $return_data = array();

        foreach ($results as $result) {

            $hospital = Hospital::findOrFail($result->hospital_id);
            $agreement = Agreement::findOrFail($result->agreement_id);
            $practice = Practice::findOrFail($result->practice_id);
            $physician = Physician::findOrFail($result->physician_id);
            $contract = Contract::findOrFail($result->contract_id);
            // log::info('contract', array($contract));
            $contract_name = Contract::select('contract_names.name as contract_name')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                ->first();

            $rejection_rate = $result->logs == 0 ? 0 : (($result->rejected_logs / $result->logs) * 100);

            $return_data[] = [
                "physician_id" => $physician->id,
                "physician_name" => $physician->first_name . ' ' . $physician->last_name,
                "practice_name" => $practice->name,
                "contract_name" => $contract_name['contract_name'],
                "rejection_count" => $result->rejected_logs,
                "logs_count" => $result->logs,
                "rate" => number_format($rejection_rate, 2),
                "hospital_name" => $hospital->name
            ];
        }

        $data = collect($return_data)->sortBy('rate')->reverse()->toArray();

        return $data;
    }

    public static function fetch_rejection_overall_rate($user_id = 0, $facility)
    {
        ini_set('max_execution_time', 6000);
        $agreement_list = Agreement::select("agreements.id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where("agreements.is_deleted", "=", "0")
            ->whereNull("agreements.deleted_at");
        $agreement_list = $agreement_list->distinct()->get();

        $total_log = 0;
        $total_rejection_log = 0;
        $total_rate = 0;


        if (count($agreement_list) > 0) {
            foreach ($agreement_list as $agreement) {
                $startEndDatesForYear = Agreement::getAgreementStartDateForYear($agreement->id);

                $logs = PhysicianLog::select("physician_logs.id")
                    ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->where("agreements.id", "=", $agreement->id)
                    ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                    ->whereNull("physician_logs.deleted_at")
                    ->count();

                if ($logs > 0) {
                    $rejection_logs = PhysicianLog::select("physician_logs.id")
                        // ->join("log_approval_history","log_approval_history.log_id", "=", "physician_logs.id")
                        ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                        ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->where("log_approval.role", "=", "1")
                        ->where("log_approval.approval_status", "=", "0")
                        // ->where("log_approval_history.approval_status", "=","0")
                        //->whereIn("physician_logs.physician_id", $physician_list->toArray())
                        ->where("agreements.id", "=", $agreement->id)
                        ->whereNull("physician_logs.deleted_at")
                        ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                        ->count();

                    $total_log += $logs;
                    $total_rejection_log += $rejection_logs;
                }
            }
            $total_rate = $total_log == 0 ? 0 : $total_rejection_log / $total_log * 100;
        }

        $return_data = [
            "id" => 101,
            "name" => "Ovarall DYNAFIOS Average",
            "total_rate" => $total_rate
        ];

        return $return_data;
    }

    public static function fetch_compliance_rejection_overall_rate($user_id = 0, $facility, $orgnizationrateid)
    {
        ini_set('max_execution_time', 6000);

        if ($facility != "0") {
            $hospital[] = $facility;
            $results = HospitalLog::get_hospital_logs_for_popup($hospital);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->distinct()
                ->get();

            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }
            $results = HospitalLog::get_hospital_logs_for_popup($hospital_list);
        }

        $return_data = array();

        foreach ($results as $result) {

            $hospital = Hospital::findOrFail($result->hospital_id);
            $agreement = Agreement::findOrFail($result->agreement_id);
            $practice = Practice::findOrFail($result->practice_id);
            $physician = Physician::findOrFail($result->physician_id);
            $contract = Contract::findOrFail($result->contract_id);

            $contract_name = Contract::select('contract_names.name as contract_name')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                ->first();

            $rejection_rate = $result->logs == 0 ? 0 : (($result->rejected_logs / $result->logs) * 100);

            $return_data[] = [
                "physician_id" => $physician->id,
                "physician_name" => $physician->first_name . ' ' . $physician->last_name,
                "practice_name" => $practice->name,
                "contract_name" => $contract_name ? $contract_name['contract_name'] : '',
                "start_date" => date("m/d/Y", strtotime($agreement->start_date)),
                "end_date" => date("m/d/Y", strtotime($agreement->end_date)),
                "rejection_count" => $result->rejected_logs,
                "logs_count" => $result->logs,
                "rate" => number_format($rejection_rate, 2),
                "hospital_name" => $hospital->name
            ];
        }

        $data = collect($return_data)->sortBy('rate')->reverse()->toArray();

        return $data;
    }

    public static function updateTotalAndRejectedLogs($hospital_id = 0)
    {
        ini_set('max_execution_time', 6000);
        if ($hospital_id > 0) {
            $hospitals = Hospital::select('hospitals.id')
                ->where('hospitals.id', '=', $hospital_id)
                ->get();
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->whereNull('hospitals.deleted_at')
                ->distinct()
                ->get();
        }

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                $practices = Practice::select('practices.id', 'agreements.id as agreement_id')
                    ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
                    ->where('agreements.hospital_id', '=', $hospital->id)
                    ->whereNull('practices.deleted_at')
                    ->where('agreements.is_deleted', '=', 0)
                    ->whereNull('agreements.deleted_at')
                    ->distinct()
                    ->get();

                if (count($practices)) {
                    foreach ($practices as $practice) {
                        $physicians = Physician::select('physicians.id')
                            // ->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
                            ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
                            ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                            // ->where('contracts.practice_id', '=', $practice->id)
                            ->where('physician_contracts.practice_id', '=', $practice->id)
                            ->where('contracts.agreement_id', '=', $practice->agreement_id)
                            ->whereNull('physicians.deleted_at')
                            ->distinct()
                            ->get();

                        if (count($physicians)) {
                            foreach ($physicians as $physician) {
                                $contracts = Contract::select('contracts.id')
                                    ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                                    // ->where('contracts.practice_id', '=', $practice->id)
                                    ->where('physician_contracts.practice_id', '=', $practice->id)
                                    ->where('contracts.agreement_id', '=', $practice->agreement_id)
                                    // ->where('contracts.physician_id', '=', $physician->id)
                                    ->where('physician_contracts.physician_id', '=', $physician->id)
                                    ->whereNull('contracts.deleted_at')
                                    ->distinct()
                                    ->get();

                                if (count($contracts)) {
                                    foreach ($contracts as $contract) {
                                        $logs = 0;
                                        $rejected_logs = 0;
                                        $startEndDatesForYear = Agreement::getAgreementStartDateForYear($practice->agreement_id);

                                        $logs = PhysicianLog::select('physician_logs.*')
                                            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                            ->whereNull('physician_logs.deleted_at')
                                            ->where('contracts.id', '=', $contract->id)
                                            ->where("contracts.agreement_id", "=", $practice->agreement_id)
                                            ->where("physician_logs.physician_id", "=", $physician->id)
                                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                                            ->count();

                                        $rejected_logs = PhysicianLog::select("physician_logs.*")
                                            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                                            ->join("contracts", "contracts.id", "=", "physician_logs.contract_id")
                                            ->where("log_approval.role", "=", "0")
                                            ->where("log_approval.approval_status", "=", "0")
                                            ->where("log_approval.reason_for_reject", ">", "0")
                                            ->where('contracts.id', '=', $contract->id)
                                            ->where("contracts.agreement_id", "=", $practice->agreement_id)
                                            ->where("physician_logs.physician_id", "=", $physician->id)
                                            ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                                            ->whereNull("physician_logs.deleted_at")
                                            ->count();

                                        if ($logs > 0) {
                                            // Check exist
                                            $check_exist = HospitalLog::select('*')
                                                ->where('hospital_id', '=', $hospital->id)
                                                ->where('agreement_id', '=', $practice->agreement_id)
                                                ->where('practice_id', '=', $practice->id)
                                                ->where('physician_id', '=', $physician->id)
                                                ->where('contract_id', '=', $contract->id)
                                                ->first();

                                            if ($check_exist) {
                                                // Update total and rejected logs
                                                HospitalLog::where('hospital_id', '=', $hospital->id)
                                                    ->where('agreement_id', '=', $practice->agreement_id)
                                                    ->where('practice_id', '=', $practice->id)
                                                    ->where('physician_id', '=', $physician->id)
                                                    ->where('contract_id', '=', $contract->id)
                                                    ->update(['logs' => $logs, 'rejected_logs' => $rejected_logs]);
                                            } else {
                                                // Add new entry
                                                $hospital_logs = new HospitalLog();
                                                $hospital_logs->hospital_id = $hospital->id;
                                                $hospital_logs->agreement_id = $practice->agreement_id;
                                                $hospital_logs->practice_id = $practice->id;
                                                $hospital_logs->physician_id = $physician->id;
                                                $hospital_logs->contract_id = $contract->id;
                                                $hospital_logs->logs = $logs;
                                                $hospital_logs->rejected_logs = $rejected_logs;
                                                $hospital_logs->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return 1;
    }

    public static function getPhysicianLogsInApprovalQueue($agreement_id, $contract_id)
    {
        if (isset($contract_id) && $contract_id != "null") {
            $contracts = Contract::where('id', '=', $contract_id)->pluck('id')->toArray();
        } else {
            $contracts = Contract::where('agreement_id', '=', $agreement_id)->where('default_to_agreement', '=', '1')->pluck('id')->toArray();
        }

        if (count($contracts) > 0) {
            $logs = self::select('*')
                ->whereIn('contract_id', $contracts)
                ->where('approval_date', '=', '0000-00-00')
                ->where('next_approver_level', '>', 0)
                ->where('approval_date', '=', '0000-00-00')
                ->count();

            return $logs;
        } else {
            return 0;
        }
    }

    public static function getMonthlyHourlySummary($recent_logs, $contract, $physician_id)
    {
        $contract_begin_date = date('Y-m-d', strtotime('-364 days', strtotime(now())));
        $contract_end_date = date('Y-m-d', strtotime(now()));
        $today = date('Y-m-d', strtotime(now()));

        usort($recent_logs, function ($a, $b) {
            return strtotime($a["date"]) - strtotime($b["date"]);
        });
        $hourly_summary = [];
        $data = [];
        $annual_remaining = 0.0;
        if ($today >= $contract_begin_date && $today <= $contract_end_date) {
            $durations = 0;
            while ($contract_begin_date <= $contract_end_date) {
                $worked_hours = PhysicianLog::where('physician_logs.contract_id', '=', $contract->id)
                    // ->where('physician_logs.physician_id', '=', $physician_id)
                    ->whereBetween('physician_logs.date', [date('Y-m-d', strtotime($contract_begin_date)), date('Y-m-t', strtotime($contract_begin_date))])
                    ->whereNull('physician_logs.deleted_at')
                    ->sum('physician_logs.duration');

                if ($worked_hours > 0) {
                    $durations += $worked_hours;
                    $hourly_summary [] = [
                        'payment_type_id' => $contract->payment_type_id,
                        'month_year' => date('m-Y', strtotime($contract_begin_date)),
                        'start_date' => date('Y-m-d', strtotime($contract_begin_date)),
                        'end_date' => date('Y-m-t', strtotime($contract_begin_date)),
                        'worked_hours' => formatNumber($worked_hours, 2),
                        'remaining_hours' => formatNumber((($contract->expected_hours - $worked_hours) > 0) ? $contract->expected_hours - $worked_hours : 0.00, 2),
                        'annual_remaining' => formatNumber((($contract->annual_cap - $durations) > 0) ? $contract->annual_cap - $durations : 0.00, 2)
                    ];
                }
                $contract_begin_date = date('Y-m-d', strtotime('+1 months', strtotime(date('Y-m-01', strtotime($contract_begin_date)))));
            }
        }

        foreach ($hourly_summary as $hourly_summary) {
            $month_year = $hourly_summary['month_year'];

            foreach ($recent_logs as $recent_log) {
                $log_date = date('Y-m-d', strtotime($recent_log['date']));
                if ($log_date >= $hourly_summary['start_date'] && $log_date <= $hourly_summary['end_date']) {

                    /**
                     * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
                     */
                    if ($contract->prior_start_date != '0000-00-00') {
                        $contract_start_date = $contract->prior_start_date;
                    } else {
                        $contract_start_date = $contract->agreement->start_date;
                    }

                    $contractFirstYearStartDate = date('Y-m-d', strtotime($contract_start_date));
                    $contractFirstYearEndDate = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contract_start_date))));

                    if (($log_date >= $contractFirstYearStartDate) && ($log_date <= $contractFirstYearEndDate)) {
                        $hourly_summary['annual_remaining'] = formatNumber((($hourly_summary['annual_remaining'] - $contract->prior_worked_hours) > 0) ? $hourly_summary['annual_remaining'] - $contract->prior_worked_hours : 0.00, 2);
                    }

                    $data[] = $hourly_summary;
                    break;
                }
            }
        }

        $hourly_summary = array_reverse($data);
        return $hourly_summary;
    }

    public static function approverpendinglog($hospital_id)
    {
        $log_details = [];
        $approvers = PhysicianLog::select('physician_logs.next_approver_user as approver', 'users.first_name as approver_name', 'users.email as approver_email')
            ->join('users', 'users.id', '=', 'physician_logs.next_approver_user')
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->where('physician_logs.next_approver_user', '>', 0)
            ->where('hospital_user.hospital_id', '=', $hospital_id)
            ->whereNull('physician_logs.deleted_at')
            ->distinct()
            ->get();

        foreach ($approvers as $approver) {
            $contracts = PhysicianLog::select('contracts.id as contract_id', 'contract_names.name as contract_name', 'physicians.id as physician_id', 'physicians.first_name as physician_first_name', 'physicians.last_name as physician_last_name')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                // ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->where('physician_logs.signature', '=', 0)
                ->where('physician_logs.approval_date', '=', '0000-00-00')
                ->where('physician_logs.approved_by', '=', 0)
                ->where('physician_logs.next_approver_user', '=', $approver->approver)
                ->where('agreements.hospital_id', '=', $hospital_id)
                ->where('contracts.archived', '=', false)
                ->where('contracts.manually_archived', '=', false)
                ->whereNull('contracts.deleted_at')
                ->whereNull('agreements.deleted_at')
                ->whereNull('physicians.deleted_at')
                ->distinct()
                ->get();

            $log_details = [];
            foreach ($contracts as $contract) {
                $logs = PhysicianLog::select(DB::raw('physician_logs.date as log_date, YEAR(physician_logs.date) as year, MONTH(physician_logs.date) as month, MONTHNAME(physician_logs.date) as month_name'))
                    ->where('physician_logs.signature', '=', 0)
                    ->where('physician_logs.approval_date', '=', '0000-00-00')
                    ->where('physician_logs.approved_by', '=', 0)
                    ->where('physician_logs.contract_id', '=', $contract->contract_id)
                    ->where('physician_logs.next_approver_user', '=', $approver->approver)
                    ->groupBy('month')
                    ->groupBy('year')
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get();

                if (count($logs) > 0) {
                    foreach ($logs as $log) {
                        $log_details[] = [
                            'physician_name' => $contract->physician_first_name . ' ' . $contract->physician_last_name,
                            'contract_name' => $contract->contract_name,
                            'period' => $log->month_name . ' ' . $log->year
                        ];
                    }
                }
            }

            if (count($log_details) > 0) {
                $data = [
                    'name' => $approver->approver_name,
                    'email' => $approver->approver_email,
                    'type' => EmailSetup::NEXT_APPROVER_PENDING_LOG,
                    'with' => [
                        'name' => $approver->approver_name,
                        'log_details' => $log_details
                    ]
                ];

                try {
                    EmailQueueService::sendEmail($data);
                } catch (Exception $e) {
                    log::error('Error Approver Pending Log Monday Morning Email: ' . $e->getMessage());
                }
            }
        }

    }

    public static function getMonthlyHourlySummaryRecentLogs($recent_logs, $contract, $physician_id)
    {
        set_time_limit(0);
        $today = date('Y-m-d', strtotime(now()));

        $agreement = Agreement::FindOrFail($contract->agreement_id);
        /**
         * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
         */
        if ($contract->prior_start_date != '0000-00-00') {
            $contract_begin_date = date('Y-m-d', strtotime($contract->prior_start_date));
        } else {
            $contract_begin_date = date('Y-m-d', strtotime($agreement->start_date));
        }
        $contract_end_date = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contract_begin_date))));
        $tempCYTD = [];

        $set = false;
        while (!$set) {
            if ((date('Y-m-d', strtotime($today)) >= $contract_begin_date) && (date('Y-m-d', strtotime($today)) <= $contract_end_date)) {
                $set = true;
                $tempCYTD[] = [
                    'cytd_start' => $contract_begin_date,
                    'cytd_end' => $contract_end_date,
                    'current_cytd' => $set
                ];
            } else {
                $set = false;
                $tempCYTD[] = [
                    'cytd_start' => $contract_begin_date,
                    'cytd_end' => $contract_end_date,
                    'current_cytd' => $set
                ];
                $contract_begin_date = date('Y-m-d', strtotime('+1 days', strtotime($contract_end_date)));
                $contract_end_date = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contract_begin_date))));
            }
        }

        usort($recent_logs, function ($a, $b) {
            return strtotime($a["date"]) - strtotime($b["date"]);
        });

        if (count($recent_logs) > 0) {
            $min_recent_log_date = $recent_logs[0]['date'];

            if (date('Y-m-d', strtotime($contract_begin_date)) > date('Y-m-d', strtotime($min_recent_log_date))) {
                $contract_begin_date = date('Y-m-d', strtotime($min_recent_log_date));
            }
        }

        $hourly_summary = [];
        $data = [];
        $annual_remaining = 0.0;

        $durations = 0;
        $computed_month = [];
        $computed_month_prev_cytd = [];
        while ($contract_begin_date <= $contract_end_date) {
            $worked_hours_cytd = 0.00;
            foreach ($tempCYTD as $cytd) {
                if (date('Y-m-d', strtotime($contract_begin_date)) >= date('Y-m-d', strtotime($cytd['cytd_start'])) && date('Y-m-d', strtotime($contract_begin_date)) <= date('Y-m-d', strtotime($cytd['cytd_end']))) {

                    if ($cytd['current_cytd']) {
                        if (!in_array($cytd['cytd_start'], $computed_month)) {
                            $durations = 0;
                            $contract_begin_date = date('Y-m-d', strtotime($cytd['cytd_start']));
                            array_push($computed_month, $cytd['cytd_start']);
                        }
                    } else {

                        if (!in_array($cytd['cytd_start'], $computed_month_prev_cytd)) {
                            $worked_hours_cytd = PhysicianLog::where('physician_logs.contract_id', '=', $contract->id)
                                // ->where('physician_logs.physician_id', '=', $physician_id)
                                ->whereBetween('physician_logs.date', [date('Y-m-d', strtotime($cytd['cytd_start'])), date('Y-m-t', strtotime('-1 months', strtotime($contract_begin_date)))])
                                ->whereNull('physician_logs.deleted_at')
                                ->sum('physician_logs.duration');
                            array_push($computed_month_prev_cytd, $cytd['cytd_start']);
                        }
                    }
                }
            }
            $worked_hours = PhysicianLog::where('physician_logs.contract_id', '=', $contract->id)
                // ->where('physician_logs.physician_id', '=', $physician_id)
                ->whereBetween('physician_logs.date', [date('Y-m-d', strtotime($contract_begin_date)), date('Y-m-t', strtotime($contract_begin_date))])
                ->whereNull('physician_logs.deleted_at')
                ->sum('physician_logs.duration');

            if ($worked_hours > 0) {
                $durations += $worked_hours + $worked_hours_cytd;
                $hourly_summary [] = [
                    'payment_type_id' => $contract->payment_type_id,
                    'month_year' => date('m-Y', strtotime($contract_begin_date)),
                    'start_date' => date('Y-m-d', strtotime($contract_begin_date)),
                    'end_date' => date('Y-m-t', strtotime($contract_begin_date)),
                    'worked_hours' => formatNumber($worked_hours, 2),
                    'remaining_hours' => formatNumber((($contract->expected_hours - $worked_hours) > 0) ? $contract->expected_hours - $worked_hours : 0.00, 2),
                    'annual_remaining' => formatNumber((($contract->annual_cap - $durations) > 0) ? $contract->annual_cap - $durations : 0.00, 2)
                ];
            }
            $contract_begin_date = date('Y-m-d', strtotime('+1 months', strtotime(date('Y-m-01', strtotime($contract_begin_date)))));
        }

        foreach ($hourly_summary as $hourly_summary) {
            $month_year = $hourly_summary['month_year'];

            foreach ($recent_logs as $recent_log) {
                $log_date = date('Y-m-d', strtotime($recent_log['date']));
                if ($log_date >= $hourly_summary['start_date'] && $log_date <= $hourly_summary['end_date']) {

                    /**
                     * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
                     */
                    if ($contract->prior_start_date != '0000-00-00') {
                        $contract_start_date = $contract->prior_start_date;
                    } else {
                        $contract_start_date = $contract->agreement->start_date;
                    }

                    $contractFirstYearStartDate = date('Y-m-d', strtotime($contract_start_date));
                    $contractFirstYearEndDate = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contract_start_date))));

                    if (($log_date >= $contractFirstYearStartDate) && ($log_date <= $contractFirstYearEndDate)) {
                        $hourly_summary['annual_remaining'] = formatNumber((($hourly_summary['annual_remaining'] - $contract->prior_worked_hours) > 0) ? $hourly_summary['annual_remaining'] - $contract->prior_worked_hours : 0.00, 2);
                    }

                    $data[] = $hourly_summary;
                    break;
                }
            }
        }

        $hourly_summary = array_reverse($data);
        return $hourly_summary;
    }

    public static function getRecentLogs($contract, $physicianId = 0)
    {
        if (($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->deadline_option == '1') {
            $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
            $contract_Deadline = $contract_deadline_days_number->contract_deadline_days;
        } else {
            $contract_Deadline = 90;
        }

        $contract_actions = Action::getActions($contract);
        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date > date(now() - interval " . $contract_Deadline . " day)")
            ->where('physician_logs.physician_id', '=', $physicianId)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //call-coverage-duration  by 1254
        $log_dates = [];
        $user_list = [];
        foreach ($recent_logs as $log) {
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }

            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call" || $log->action->name == "Called-Back" || $log->action->name == "Called-In") {
                            $duration_data = "On call full Day";
                        } else {
                            $duration_data = "Full Day";
                        }
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call/Uncompensated") {
                            $duration_data = "On call Uncompensated Day";
                        } else {
                            $duration_data = "On call Uncompensated Day";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }

            } else {
                $duration_data = formatNumber($log->duration);
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $entered_by = $log->physician->last_name . ', ' . $log->physician->first_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    if (!array_key_exists($log->entered_by, $user_list)) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->entered_by)->first();

                        $user_list[$log->entered_by] = $user->full_name;
                        $entered_by = $user_list[$log->entered_by];
                    } else {
                        $entered_by = $user_list[$log->entered_by];
                    }
                }
            }

            $approved_by = "-";
            if ($log->approving_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->approved_by > 0) {
                    $approved_by = $log->physician->last_name . ', ' . $log->physician->first_name;
                }
            } elseif ($log->approving_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->approved_by > 0) {
                    if (!array_key_exists($log->approved_by, $user_list)) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->approved_by)->first();

                        $user_list[$log->approved_by] = $user->full_name;
                        $approved_by = $user_list[$log->approved_by];
                    } else {
                        $approved_by = $user_list[$log->approved_by];
                    }
                }
            }
            $total_duration = 0.0;
            if ($contract->partial_hours == 1) {

                $physicianLog = new PhysicianLog();
                $total_duration = $physicianLog->getTotalDurationForPartialHoursOn($log->date, $log->action_id, $contract->agreement_id);

            }

            if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */
            $results[] = [
                "id" => $log->id,
                "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                "date" => format_date($log->date),
                "date_selector" => format_date($log->date, "M-Y"),
                "duration" => $duration_data,
                "created" => $created,
                "isSigned" => ($log->signature > 0) ? true : false,
                "note_present" => (strlen($log->details) > 0) ? true : false,
                "note" => (strlen($log->details) > 0) ? $log->details : '',
                "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                "approvedBy" => (strlen($approved_by) > 0) ? $approved_by : '-',
                "approvedDate" => ($log->approval_date == '0000-00-00') ? 'NotApproved' : 'Approved',
                "payment_type_id" => $contract->payment_type_id,
                "actions" => $contract_actions,
                "action_id" => $log->action_id,
                "custom_action" => $log->action->name,
                "contract_type" => $contract->contract_type_id,
                "shift" => $log->am_pm_flag,
                "mandate" => $contract->mandate_details,
                "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                "log_physician_id" => $log->physician_id,
                "isApproved" => $isApproved,
                //call-coverage-duration  by 1254
                "partial_hours" => $contract->partial_hours,
                "contract_id" => $contract->id,
                "total_duration" => $total_duration,
                "partial_hours_calculation" => $contract->partial_hours_calculation,
                "start_time" => date('g:i A', strtotime($log->start_time)),
                "end_time" => date('g:i A', strtotime($log->end_time))
            ];

        }

        return $results;
    }

    public static function getPhysicianApprovedLogs($contract, $physicianId = 0)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        /**
         * #payment_frequency
         * Below factory function is used for fetching the date range and prior_date for respective payment frequency.
         */
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];

        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where("physician_logs.physician_id", "=", $physicianId)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        $user_list = [];

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) < 1) {
                //if($contract->contract_type_id == ContractType::ON_CALL)
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if ($contract->partial_hours == true) {
                        $duration_data = formatNumber($log->duration);
                    } else {
                        if ($log->duration == 1.00) {
                            $duration_data = "Full Day";
                        } elseif ($log->duration == 0.50) {
                            if ($log->am_pm_flag == 1) {
                                $duration_data = "AM";
                            } else {
                                $duration_data = "PM";
                            }
                        } else {
                            $duration_data = formatNumber($log->duration);
                        }
                    }
                } else {
                    $duration_data = formatNumber($log->duration);
                }

                $entered_by = "Not available.";
                if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                    if ($log->entered_by > 0) {
                        $entered_by = $log->physician->last_name . ', ' . $log->physician->first_name;
                    }
                } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                    if ($log->entered_by > 0) {
                        if (!array_key_exists($log->entered_by, $user_list)) {
                            $user = DB::table('users')
                                ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                                ->where('id', '=', $log->entered_by)->first();

                            $user_list[$log->entered_by] = $user->full_name;
                            $entered_by = $user_list[$log->entered_by];
                        } else {
                            $entered_by = $user_list[$log->entered_by];
                        }
                    }
                }

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    $custom_action = CustomCategoryActions::select('*')
                        ->where('contract_id', '=', $contract->id)
                        ->where('action_id', '=', $log->action_id)
                        ->where('action_name', '!=', null)
                        ->where('is_active', '=', true)
                        ->first();

                    $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
                }

                $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

                foreach ($date_range_res as $date_range_obj) {
                    if (strtotime($log->date) >= strtotime($date_range_obj['start_date']) && strtotime($log->date) <= strtotime($date_range_obj['end_date'])) {
                        $date_selector = format_date($date_range_obj['start_date'], "m/d/Y") . " - " . format_date($date_range_obj['end_date'], "m/d/Y");
                    }
                }

                $results[] = [
                    "id" => $log->id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    "date_selector" => $date_selector,
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                    "actions" => Action::getActions($contract),
                    "action_id" => $log->action_id,
                    "custom_action" => $log->action->name,
                    "contract_type" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "shift" => $log->am_pm_flag,
                    "mandate" => $contract->mandate_details,
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "log_physician_id" => $log->physician_id,
                    "log_physician_id" => $log->physician_id,
                    //call-coverage-duration  by 1254 : added partial hours
                    "partial_hours" => $contract->partial_hours,
                    "start_time" => date('g:i A', strtotime($log->start_time)),
                    "end_time" => date('g:i A', strtotime($log->end_time))
                ];
            }
        }

        return $results;
    }

    public static function getContractStatistics($contract)
    {
        $monthNum = 3;
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
        }

        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        $date_range_res_count = count($date_range_res);

        if ($date_range_res_count == 1) {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
            $date = with(new DateTime($prior_start_date));
            $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
            $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
        }

        $expected_months = $agreement_data->current_month;
        $expected_hours = $contract->expected_hours * $expected_months;

        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
                ->where("physician_logs.date", "<=", now())
                ->sum("duration");
            $expected_hours = $contract->expected_hours;
        } else {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
                ->where("physician_logs.date", "<=", now())
                ->sum("duration");
        }

        if ($contract->quarterly_max_hours == 1) {
            $remaining_hours = $contract->max_hours - $worked_hours;
        } else {
            $remaining_hours = $expected_hours - $worked_hours;
        }

        $get_total_hours_and_count = $contract->logs()->select(DB::raw("SUM(physician_logs.duration) as total_hours"), DB::raw("COUNT(physician_logs.id) as saved_logs_count"))
            ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
            ->where("physician_logs.date", "<=", now())
            ->get();
//            ->sum("duration");

//        $saved_logs = $contract->logs()
//            ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
//            ->where("physician_logs.date", "<=", now())
//            ->count();

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($get_total_hours_and_count[0]->total_hours),
            "saved_logs" => $get_total_hours_and_count[0]->saved_logs_count,
            "annual_cap" => formatNumber($contract->annual_cap)
        ];
    }

    public static function currentMonthLogDayDuration($contract)
    {

        $agreement_data = Agreement::getAgreementData($contract->agreement_id);
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $date_range_with_start_end_date = $res_pay_frequency['date_range_with_start_end_date'];
        $arr_length = count($date_range_with_start_end_date) - 1;

        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');

        $today = date('Y-m-d');

        for ($arr_length; $arr_length >= 0; $arr_length--) {
            $date_obj = $date_range_with_start_end_date[$arr_length];

            if (strtotime($today) >= strtotime($date_obj['start_date']) && strtotime($today) <= strtotime($date_obj['end_date'])) {
                $start_date = $date_obj['start_date'];
                $end_date = $date_obj['end_date'];
                break;
            }
        }

        // log::info('Test', array($res_pay_frequency));

        // $start_date = date('Y-m-01');
        // $end_date = date('Y-m-t');

        return $total_days_hours = $contract->logs()
            ->whereBetween("physician_logs.date", [mysql_date($start_date), mysql_date($end_date)])
            ->sum('physician_logs.duration');

        //    Log::Info('totalHours', array($contract->partial_hours));
    }

    public static function getPriorContractStatistics($contract)
    {

        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        }

        $date_range_res_count = count($date_range_res);

        if ($date_range_res_count > 4) {
            $prior_period_start_date = $date_range_res[$date_range_res_count - 4]['start_date']; //for fetching last prior 3 periods data
        } else {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            if ($date_range_res_count == 1) {
                $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
                $date = with(new DateTime($prior_start_date));
                $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
                $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
            }
        }

        $start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
        $current_date = date('Y-m-d h:m:s', strtotime(mysql_date($agreement_month->now_date)));
        //$prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_data->end_date)));
        // $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_months = $agreement_data->current_month;
        $expected_hours = $contract->expected_hours;

        $current_month_start_date_time = new DateTime($agreement_month->start_date);

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", $current_month_start_date_time)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");

        $total_prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->sum("duration");
        $remaining_hours = $expected_hours - $worked_hours;
        /*if ($worked_hours < $expected_hours){
            $remaining_hours = $expected_hours - $worked_hours;
        }else{
            $remaining_hours = $worked_hours - $expected_hours;
        }*/

        //$total_hours = $contract->logs()->sum("duration");
        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->count();

        $prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($prior_period_start_date))
            ->where("physician_logs.date", "<=", mysql_date($prior_month_end_date))
            //->where("physician_logs.created_at", ">=", $current_month_start_date_time->format('Y-m-d 00:00:00'))
            //->where("physician_logs.created_at", "<=", date('Y-m-d H:i:s'))
            // ->where("physician_logs.date", "<=", date('Y-m-d H:i:s'))
            ->sum("duration");

//        if(($contract->payment_type_id == PaymentType::HOURLY)&&($contract->prior_start_date!='0000-00-00'))
        if (($contract->payment_type_id == PaymentType::HOURLY)) {
            /**
             * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
             */
            if ($contract->prior_start_date != '0000-00-00') {
                $contract_start_date = $contract->prior_start_date;
            } else {
                $contract_start_date = $contract->agreement->start_date;
            }
            $contractFirstYearStartDate = date('Y-m-d', strtotime($contract_start_date));

            if ($contractFirstYearStartDate >= date('Y-m-d', strtotime(mysql_date($prior_period_start_date))) && ($contractFirstYearStartDate <= date('Y-m-d', strtotime(mysql_date($prior_month_end_date))))) {
                $prior_worked_hours = $prior_worked_hours + $contract->prior_worked_hours;
            }
        }

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => $saved_logs,
            "prior_worked_hours" => formatNumber($prior_worked_hours),
            "total_prior_worked_hours" => formatNumber($total_prior_worked_hours)
        ];
    }

    public function physician()
    {
        return $this->belongsTo('App\Physician');
    }

    public function contract()
    {
        return $this->belongsTo('App\Contract');
    }

    public function action()
    {
        return $this->belongsTo('App\Action');
    }

    public function submitMultipleLogForOnCall($action_id, $shift, $log_details, $physician_id, $contract_id, $selected_dates, $log_duration, $start_time, $end_time)
    {
        return $this->submitLogForOnCall($action_id, $shift, $log_details, $physician_id, $contract_id, $selected_dates, $log_duration, $start_time, $end_time);
    }

    private function submitLogForOnCall($action_id, $shift, $log_details, $physician_id, $contract_id, $selected_dates, $log_duration, $start_time, $end_time)
    {
        $physician = Physician::findOrFail($physician_id);
        $user_id = $physician_id;
        $getAgreementID = DB::table('contracts')
            ->where('id', '=', $contract_id)
            ->pluck('agreement_id');
        $agreement = Agreement::findOrFail($getAgreementID);
        $contract = Contract::findOrFail($contract_id);

        $start_date = $agreement[0]->start_date;
        //$end_date=$agreement->end_date;
        $end_date = $contract->manual_contract_end_date;


        $hours = DB::table("action_contract")
            ->select("hours")
            ->where("contract_id", "=", $contract_id)
            ->where("action_id", "=", $action_id)
            ->first();

        if ($hours != "")
            $duration = $hours->hours;
        else
            $duration = 0;
        //$selected_dates_array = explode(',', $selected_dates);
        //call-coverage by 1254
        if ($contract->partial_hours == 1 && ($contract->payment_type_id == 3 || $contract->payment_type_id == 5)) {
            $duration = $log_duration;
        }

        $log_error_for_dates = [];
        foreach ($selected_dates as $selected_date) {
            $user = self::where('physician_id', '=', $physician_id)
                ->where('date', '=', mysql_date($selected_date))
                ->where('contract_id', '=', $contract_id)
                ->first();

            if ((mysql_date($selected_date) >= $start_date) && (mysql_date($selected_date) <= $end_date)) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                } else {
                    $contract_Deadline_number_string = '-90 days';
                }
                if (strtotime($selected_date) > strtotime($contract_Deadline_number_string)) {
                    // Start - Server side validation for approved months logs not allowed
                    $payment_type_factory = new PaymentFrequencyFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->first()->payment_frequency_type);
                    $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement->first());
                    $period_dates = $res_pay_frequency['date_range_with_start_end_date'];

                    foreach ($period_dates as $dates_obj) {
                        if (strtotime(mysql_date($selected_date)) >= strtotime(mysql_date($dates_obj['start_date'])) && strtotime(mysql_date($selected_date)) <= strtotime(mysql_date($dates_obj['end_date']))) {
                            $check_approved_period = PhysicianLog::select("physician_logs.*")
                                ->whereNull('physician_logs.deleted_at')
                                ->where('physician_logs.contract_id', '=', $contract->id)
                                ->where('physician_logs.physician_id', '=', $physician_id)
                                ->whereBetween('physician_logs.date', [mysql_date($dates_obj['start_date']), mysql_date($dates_obj['end_date'])])
                                ->where('next_approver_level', '!=', 0)
                                ->where('next_approver_user', '!=', 0)
                                ->get();
                            if (count($check_approved_period) > 0) {

                                return "Can not select date from the range of approved logs. (" . $dates_obj['start_date'] . "-" . $dates_obj['end_date'] . ")";

                            }
                        }
                    }
                    // End - Server side validation for approved months logs not allowed
                    //some server side validation
                    //fetch already entered logs for the physician, same contract, same date

                    $results = $this->validateLogEntryForSelectedDate($physician_id, $contract, $contract_id, $selected_date, $action_id, $shift, $log_details, $physician, $duration, $user_id);

                    if ($results->getdata()->message == "Success") {
                        $logstatus = $this->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
                        if ($logstatus === 'Success') {
                            // return Response::json([
                            //     "status" => self::STATUS_SUCCESS,
                            //     "message" => Lang::get("api.save")
                            // ]);
                        } else if ($logstatus === 'practice_error') {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.practice_not_present")
                            ]);
                        } else if ($logstatus === 'Error') {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.contract_not_exist")
                            ]);
                        } else {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Request time out"
                            ]);
                        }
                    } else {
                        if ($results->getdata()->message == "annual_max_shifts_error") {
                            return Lang::get("api.annual_max_shifts_error");
                        } else {
                            $log_error_for_dates[$selected_date] = $results->getdata()->message;
                        }
                    }
                } else {
                    return "Error 90_days";
                }
            }
        }

        if (count($log_error_for_dates) > 0) {
            $final_error_message = "You are not allowed to enter log for dates :";
            $cnt = 1;
            foreach ($log_error_for_dates as $error_date => $error_message) {
                $datetime = strtotime($error_date);
                $format_date = date('d-M', $datetime);
                $final_error_message .= "$format_date" . ",";
                if ($cnt > 2) {
                    $final_error_message .= "\n";
                    $cnt = 1;
                }
                $cnt = $cnt + 1;
            }
            $final_error_message = rtrim($final_error_message, ',');
            $final_error_message .= ".";
            //call-coverage-duration  by 1254 : added error message for total hrs exceed than 24hrs
            if ($contract->partial_hours == 1 && $contract->payment_type_id == 3) {
                if ($results == "Error") {
                    return "You are not allowed to enter log for log hours exceeds than 24hrs.";;
                }
            }
            return $final_error_message;

        } else {
            return "Success";
        }
    }

    public function validateLogEntryForSelectedDate($physician_id, $contract, $contract_id, $selected_date, $action_id, $shift, $log_details, $physician, $duration, $user_id)
    {
        //some server side validation
        $practice_info = DB::table('contracts')
            ->select('physician_contracts.practice_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'physician_contracts.practice_id')
            ->where('contracts.id', '=', $contract_id)
            ->where('physician_contracts.physician_id', '=', $physician_id)
            ->where('physician_practice_history.start_date', '<=', mysql_date($selected_date))
            ->where('physician_practice_history.end_date', '>=', mysql_date($selected_date))
            ->first();
        if ($practice_info) {
            //fetch already entered logs for the physician, same contract, same date
            $save_flag = 1;
            $approvedLogsMonth = $this->getApprovedLogsMonthsAPI($contract);
            $logdate_formatted = date_parse_from_format("Y-m-d", $selected_date);

            foreach ($approvedLogsMonth as $approvedMonth) {
                $approvedMonth_formatted = date_parse_from_format("m-d-Y", $approvedMonth);
                if ($approvedMonth_formatted["year"] == $logdate_formatted["year"]) {
                    if ($approvedMonth_formatted["month"] == $logdate_formatted["month"]) {
                        $save_flag = 0;

                    }
                }
            }

            if ($save_flag == 0) {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => "Error"
                ]);

            }

            if ($contract->burden_of_call == 1 && $contract->on_call_process == 1) {
                // $logdata = self::where('physician_id', '=', $physician_id)
                $logdata = self::where('contract_id', '=', $contract_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->whereNull('deleted_at')
                    ->get();

            } else {
                // $logdata = self::where('physician_id', '=', $physician_id)
                $logdata = self::where('contract_id', '=', $contract_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->where('action_id', '=', $action_id)
                    ->whereNull('deleted_at')
                    ->get();
            }
            //call-coverage-duration  by 1254 : validation for partial_hours exceed than 24hrs
            $total_duration = $this->getTotalDurationForPartialHoursOn($selected_date, $action_id, $contract->agreement_id);


            if (($total_duration + $duration) > 24.00) {

                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => "Error"
                ]);
            }

            //6.1.1.12 start
            if (($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->partial_hours == 0) {
                // Uncompensated partial off validation for one contract many physician if p1 added a log to a date then p2 shall not allowed.

                $contract_log_present_on_date = self::where('contract_id', '=', $contract_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->whereNull('deleted_at')
                    ->first();

                if ($contract_log_present_on_date) {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => "Log is already present."
                    ]);
                }
            } else if (($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->partial_hours == 1) {

                $contract_log_present_on_date = self::where('contract_id', '=', $contract_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->whereNull('deleted_at')
                    ->get();

                if (count($contract_log_present_on_date) > 0) {
                    $logged_duration = $contract_log_present_on_date->sum('duration');

                    if (($logged_duration + $duration) > $contract->partial_hours_calculation) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "Can not log hours more than partial hours."
                        ]);
                    }
                }
            }

            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->on_call_process == 0 && $contract->partial_hours == 0) {
                    $contract_logs_present_on_date = self::where('contract_id', '=', $contract_id)
                        ->where('date', '=', mysql_date($selected_date))
                        ->whereNull('deleted_at')
                        ->get();

                    if (count($contract_logs_present_on_date) > 0) {
                        foreach ($contract_logs_present_on_date as $log) {
                            if ($log->am_pm_flag == 0) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => "Log is already present."
                                ]);
                            } else if ($log->am_pm_flag == $shift) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => "Log is already present."
                                ]);
                            }
                        }
                    }
                }
            }

            //6.1.1.12 end

            // Annual max shifts Start Sprint 6.1.17
            if ($contract->payment_type_id == PaymentType::PER_DIEM && $contract->annual_cap > 0) {
                $annual_max_shifts = self::annual_max_shifts($contract, $selected_date, $duration, "");
                if ($annual_max_shifts) {
                    return $annual_max_shifts;
                }
            }
            // Annual max shifts End Sprint 6.1.17

            $action_selected = Action::findOrFail($action_id);
            //if logs not yet entered , can be able to add (save) any log
            if (count($logdata) == 0) {
                //If called in and call back the not allow for no log
                if ($contract->burden_of_call == 0 || ($action_selected->name != "Called-In" && $action_selected->name != "Called-Back")) {

                    return Response::json([
                        "status" => self::STATUS_SUCCESS,
                        "message" => "Success"
                    ]);

                } else {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => "Error"
                    ]);
                }

            } else {
                $enteredLogEligibility = 0;
                if (($contract->on_call_process == 1 && $contract->burden_of_call == 0) && (count($logdata) == 1)) {

                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => "Error"
                    ]);
                }

                foreach ($logdata as $logdata) {
                    $ampmflag = $logdata->am_pm_flag;
                    // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                    if (($ampmflag != 0) && ($shift != 0)) {
                        //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                        if ($shift != $ampmflag) {
                            /*$result= $this->saveLogs($action_id,$shift,$log_details,$physician,$contract_id,$selected_date,$duration,$user_id);
                            if($result != "Success" ){
                                return $result;
                            }*/
                            $enteredLogEligibility = 1;
                        }
                    }
                    //If on call then allow to enter called in and call back
                    $action_present = Action::findOrFail($logdata->action_id);
                    if ($contract->burden_of_call == 0 || $action_present->name == "On-Call") {
                        if ($contract->burden_of_call == 0 || $action_selected->name != "On-Call") {
                            /*$result = $this->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id);
                            if ($result != "Success") {
                                return $result;
                            }*/
                            $enteredLogEligibility = 1;
                        }
                    }
                }
                if ($enteredLogEligibility) {
                    return Response::json([
                        "status" => self::STATUS_SUCCESS,
                        "message" => "Success"
                    ]);
                } else {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => "Error"
                    ]);
                }
            }
        } else {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => "practice_error"
            ]);
        }

    }

    public function getApprovedLogsMonthsAPI($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }
            if ($isApproved == true) //if($log->approval_date !='0000-00-00' || $log->signature > 0)
            {
                $d = date_parse_from_format("Y-m-d", $log->date);

                if ($current_month != $d["month"]) {
                    $current_month = $d["month"];
                    $results[] = $d["month"] . '/01/' . $d["year"];
                    //$results[] = $d["month"];
                }
            }
        }

        return $results;
    }

    public function getTotalDurationForPartialHoursOn($selected_date, $log_action, $agreement_id)
    {
        $total_duration = PhysicianLog::join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->where('physician_logs.date', '=', mysql_date($selected_date))
            ->where('contracts.partial_hours', '=', 1)
            ->whereIn('contracts.payment_type_id', [3, 5])
            ->where("contracts.agreement_id", "=", $agreement_id)
            ->where("physician_logs.action_id", "=", $log_action)
            ->whereNull('physician_logs.deleted_at')
            ->sum('physician_logs.duration');

        return ($total_duration);
    }

    public static function annual_max_shifts($contract, $selected_date, $duration, $log_id)
    {
        $agreement = Agreement::FindOrFail($contract->agreement_id);
        $contract_begin_date = date('Y-m-d', strtotime($agreement->start_date));
        $contract_end_date = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($agreement->start_date))));

        $set = false;
        while (!$set) {
            if ((date('Y-m-d', strtotime($selected_date)) >= $contract_begin_date) && (date('Y-m-d', strtotime($selected_date)) <= $contract_end_date)) {
                $set = true;
            } else {
                $contract_begin_date = $contract_end_date;
                $contract_end_date = date('Y-m-d', strtotime('+1 years', strtotime($contract_begin_date)));
                $set = false;
            }
        }

        $annual_max_shifts_duration = self::where('physician_logs.contract_id', '=', $contract->id)
            ->whereBetween('physician_logs.date', array(mysql_date($contract_begin_date), mysql_date($contract_end_date)));
        if ($log_id > 0) {
            $annual_max_shifts_duration = $annual_max_shifts_duration->where('physician_logs.id', '!=', $log_id);
        }
        $annual_max_shifts_duration = $annual_max_shifts_duration->whereNull('physician_logs.deleted_at')
            ->sum('physician_logs.duration');

        $annual_max_shifts = $contract->annual_cap;
        if ($contract->partial_hours == 1) {
            $annual_max_shifts = $contract->annual_cap * 24;
        }

        if (($annual_max_shifts_duration + $duration) > $annual_max_shifts) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => "annual_max_shifts_error"
            ]);
        }
    }

    public function saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time)
    {
        return $this->saveLog($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
    }

    private function saveLog($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time)
    {

        if ($start_time != "" && $end_time != "") {
            $start_time = new DateTime($selected_date . " " . $start_time);  // $date->format('Y-m-d G:i:s');
            $end_time = new DateTime($selected_date . " " . $end_time);   // $date->format('Y/m/d G:i:s');

            if ($start_time >= $end_time) {
                return "Start And End Time";
            }

            $logs = PhysicianLog::select('*')
                ->where('contract_id', '=', $contract_id)
                ->where('physician_id', '=', $physician->id)
                ->where('date', '=', mysql_date($selected_date))
                ->where('start_time', '!=', "0000-00-00 00:00:00")
                ->where('end_time', '!=', "0000-00-00 00:00:00")
                ->get();

            if (count($logs) > 0) {
                foreach ($logs as $logs) {
                    $date = new DateTime($logs->start_time);
                    $logs_start_time = $date;   // $date->format('Y-m-d G:i:s');

                    $date = new DateTime($logs->end_time);
                    $logs_end_time = $date;     //$date->format('Y-m-d G:i:s');

                    $n_start_time = $start_time;    // ->format('Y-m-d G:i:s');
                    $n_end_time = $end_time;        // ->format('Y-m-d G:i:s');

                    if ($n_start_time <= $logs_start_time && $n_end_time <= $logs_start_time) {
                        // false
                    } else if ($n_start_time >= $logs_end_time) {
                        // false
                    } else if ($n_start_time >= $logs_start_time && $n_start_time <= $logs_end_time) {
                        return "Log Exist";
                    } else if ($n_end_time >= $logs_start_time && $n_end_time <= $logs_end_time) {
                        return "Log Exist";
                    } else if ($logs_start_time >= $n_start_time && $logs_end_time <= $n_end_time) {
                        return "Log Exist";
                    }
                }
            }
        }

        $physician_logs = new PhysicianLog();
        $physician_logs->physician_id = $physician->id;
        $physician_log_history = new PhysicianLogHistory();
        $physician_log_history->physician_id = $physician->id;

        $agreement_obj = Contract::select('agreements.hospital_id as hospital_id', 'agreements.id as agreement_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->where('contracts.id', '=', $contract_id)
            ->first();
        // $practice_info = DB::table('physician_practice_history')
        //     ->where('physician_id', '=', $physician->id)
        //     ->where('start_date', '<=', mysql_date($selected_date))
        //     ->where('end_date', '>=', mysql_date($selected_date))
        //     ->first();
//  Physician to multiple hospital by 1254 : get practice id of selected hospital and save in physician_log table
        $practice_info = DB::table('contracts')
            ->select('physician_contracts.practice_id', 'contracts.contract_name_id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'physician_contracts.practice_id')
            ->where('contracts.id', '=', $contract_id)
            ->where('physician_practice_history.start_date', '<=', mysql_date($selected_date))
            ->where('physician_practice_history.end_date', '>=', mysql_date($selected_date))
            ->first();

        if ($practice_info) {
            $physician_practice_obj = PhysicianPractices::where('practice_id', '=', $practice_info->practice_id)
                ->first();

            // if($physician_practice_obj){
            //     $selected_date_format = date('Y-m-d', strtotime($selected_date));
            //     if(strtotime($selected_date_format) <= strtotime($physician_practice_obj->start_date)){
            //         return "practice_error";
            //     }
            // }
            $physician_logs->practice_id = $practice_info->practice_id;
            $physician_log_history->practice_id = $practice_info->practice_id;

            // $check_hospital_for_practice = Practice::select('practices.*')
            //     ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
            //     ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            //     ->where('practices.id', '=', $physician_logs->practice_id)
            //     ->where('contracts.id', '=', $contract_id)
            //     // ->where('contracts.end_date1', '=', '0000-00-00 00:00:00')
            //     ->where('contracts.end_date', '=', '0000-00-00 00:00:00')
            //     ->first();
            // if (count($check_hospital_for_practice) > 0) {
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
            } else {
                $physician_logs->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                $physician_log_history->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
            }
            $physician_logs->timeZone = Request::input("timeZone") != null ? Request::input("timeZone") : ''; /*save timezone*/
            $physician_logs->am_pm_flag = $shift;
            //call-coverage-duration  by 1254 :save log_hours in table physician_logs and physician_log_history
            $contract = Contract::findOrFail($contract_id);
            if ($contract->partial_hours == 1 && ($contract->payment_type_id == 3 || $contract->payment_type_id == 5)) {
                // $physician_logs->log_hours = formatNumber($duration/24); //(1/24)*$duration
                //$physician_logs->log_hours = formatNumber((1.00/24) * $duration); //(1/24)*$duration
                // $physician_logs->log_hours = ((1.00/24) * $duration); //(1/24)*$duration

                // $physician_log_history->log_hours = formatNumber((1.00/24) * $duration); //(1/24)*$duration
                //  $physician_log_history->log_hours = ((1.00/24) * $duration); //(1/24)*$duration
                //Below line of change is added by akash.
                $physician_logs->log_hours = ((1.00 / $contract->partial_hours_calculation) * $duration); //(1/24)*$duration
                $physician_log_history->log_hours = ((1.00 / $contract->partial_hours_calculation) * $duration); //(1/24)*$duration
            } else {
                /*$physician_logs->log_hours = formatNumber($duration);
                    $physician_log_history->log_hours = formatNumber($duration);*/
                $physician_logs->log_hours = ($duration);
                $physician_log_history->log_hours = ($duration);
            }
            $physician_logs->start_time = $start_time;
            $physician_logs->end_time = $end_time;

            if ($physician_logs->save()) {
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

                if ($physician_log_history->save()) {
                    /**
                     * Below function is use to set-up the payment_status_dashboard table with precalculated data for performance improvement.
                     */
                    // $update_pmt_status_dashboard = PaymentStatusDashboard::updatePaymentStatusDashboard($physician->id, $practice_info->practice_id, $contract_id, $practice_info->contract_name_id, $agreement_obj['hospital_id'], $agreement_obj['agreement_id'], $selected_date);
                    UpdatePaymentStatusDashboard::dispatch($physician->id, $practice_info->practice_id, $contract_id, $practice_info->contract_name_id, $agreement_obj['hospital_id'], $agreement_obj['agreement_id'], $selected_date);

                    UpdateLogCounts::dispatch($agreement_obj['hospital_id']);
                }
            }

            return "Success";
            //  }
            //  else {
            //     log::info("error");
            //     return "Error";
            // }

        } else {
            return "practice_error";
        }
    }

    public function approveLogs($log_ids, $rejected, $signatureRecord, $role, $user_id, $status, $reason)
    {
        return $this->approveLog($log_ids, $rejected, $signatureRecord, $role, $user_id, $status, $reason);
    }

    /*added to check logs present for physician approval or not 7 NOV 2019*/

    private function approveLog($log_ids, $rejected, $signatureRecord, $role, $user_id, $status, $rejected_reason, $payment_type = 1)
    {
        $log_ids_length = sizeof($log_ids);
        $rejected_length = sizeof($rejected);
        //create array for reason
        if ($rejected_reason == 0) {
            $reason = 0;
        } else {
            $reason_arr = array();
            $rejected_with_reasons = explode(',', $rejected_reason);
            foreach ($rejected_with_reasons as $log_reason) {
                $log_reason = explode('_', $log_reason);
                $log_id = $log_reason[0];
                $log_reason = $log_reason[1];
                $reason_arr[$log_id] = $log_reason;
            }
        }

        for ($i = 0; $i < $log_ids_length; $i++) {
            if ($log_ids[$i] > 0) {
                $reason = 0;
                $this->approveReject($log_ids[$i], $role, $user_id, $signatureRecord, $status, $reason);
            }
        }
        for ($j = 0; $j < $rejected_length; $j++) {
            if ($rejected[$j] > 0) {
                $status = 0;
                if ($rejected_reason == 0) {
                    $reason = 0;
                } else {
                    $reason = $reason_arr[$rejected[$j]];
                }

                $this->approveReject($rejected[$j], $role, $user_id, $signatureRecord, $status, $reason);
            }
        }
        $hospitals = PhysicianPractices::select('physician_practices.hospital_id')
            ->join('physicians', 'physicians.id', '=', 'physician_practices.physician_id')
            ->join('users', 'users.email', '=', 'physicians.email')
            ->where('users.id', '=', $user_id)
            ->distinct()
            ->get();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                if ($hospital->hospital_id > 0) {
                    UpdateLogCounts::dispatch($hospital->hospital_id);
                    UpdatePendingPaymentCount::dispatch($hospital->hospital_id);
                }
            }
        } else {
            $hospitals = DB::table('hospital_user')->select('*')
                ->where('user_id', '=', $user_id)
                ->distinct()
                ->get();

            if (count($hospitals) > 0) {
                foreach ($hospitals as $hospital) {
                    if ($hospital->hospital_id > 0) {
                        UpdateLogCounts::dispatch($hospital->hospital_id);
                        UpdatePendingPaymentCount::dispatch($hospital->hospital_id);
                    }
                }
            }
        }

        if (count($log_ids) > 0) {
            // Next level approver email
            self::next_approver_send_email($log_ids);

            // Final approver email to invoice reminder
            self::final_approver_invoice_reminder_recipients_send_email($log_ids);
        }

        //return $payment_type; //commented this because of the condition handling is required for each payment type in approvelog of physician
        return 1;
    }

    //physician to multiple hospital by 1254 (Moved from ContractsController.php to here by akash)

    private function approveReject($log_id, $role, $user_id, $signatureRecord, $status, $reason)
    {
        $agreement_details = Agreement::select('agreements.*', 'contracts.id as contract_id', 'contracts.default_to_agreement as default_to_agreement')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('physician_logs', 'physician_logs.contract_id', '=', 'contracts.id')
            ->where('physician_logs.id', '=', $log_id)
            ->first();
        $contract = Contract::findOrFail($agreement_details->contract_id);
        if (!$signatureRecord) {
            $signatureId = 0;
        } else {
            $signatureId = $signatureRecord->signature_id;
        }

        if ($agreement_details->default_to_agreement != 0) {
            $checked_for_user_id = ProxyApprovalDetails::check_for_manager_id($agreement_details->id, 0, $role - 1, $user_id);
        } else {
            $checked_for_user_id = ProxyApprovalDetails::check_for_manager_id($agreement_details->id, $agreement_details->contract_id, $role - 1, $user_id);
        }

        /*time zone for signature*/
        if (Request::input("timeZone") != null && Request::input("localTimeZone") != null && Request::input("localTimeZone") != '') {
            if (!strtotime(Request::input("timeZone"))) {
                $zone = new DateTime(strtotime(Request::input("timeZone")));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            $timeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $timeZone = '';
        }
        $approval_dates = LogApproval::where("log_id", "=", $log_id)->get();
        if ($agreement_details->approval_process == 1 || count($approval_dates) > 0) {
            if ($contract->payment_type_id == PaymentType::PSA && $contract->wrvu_payments == false) {
                $physician_log = PhysicianLog::find($log_id);
                $physician_log->signature = $signatureId;
                $physician_log->approval_date = date('Y-m-d');
                $physician_log->approved_by = $user_id;
                $physician_log->approving_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                $physician_log->signatureTimeZone = $timeZone;
                $physician_log->save();
            } else {
                $level = 0;
                if (count($approval_dates) > 0) {
                    $approval_level = LogApproval::where("log_id", "=", $log_id)->where('approval_status', '=', '1')->orderBy('approval_managers_level', 'desc')->first();
                    if (empty($approval_level)) {
                        $level = 0;
                    } else {
                        $prev_level = $approval_level->approval_managers_level;
                        if ($agreement_details->default_to_agreement != 0) {
                            $next_approval_level = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where('user_id', '=', $checked_for_user_id)->where("is_deleted", "=", '0')->orderBy('level')->get();
                        } else {
                            $next_approval_level = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $agreement_details->contract_id)->where('user_id', '=', $checked_for_user_id)->where("is_deleted", "=", '0')->orderBy('level')->get();
                        }
                        foreach ($next_approval_level as $next_level) {
                            if ($next_level->level == $prev_level + 1) {
                                $level = $next_level->level;
                                break;
                            }
                        }
                        if ($level == 0) {
                            return 0;
                        }
                    }
                }
                $user_info = User::FindOrFail($user_id);
                if (!$user_info->hasAnyRole(['physician','super-user','practice-manager'])) {
                    $role = 0;
                }
                $approve_log = LogApproval::where('log_id', '=', $log_id)->where('role', '=', $role)->where('approval_managers_level', '=', $level)->first();

                $approve_log_histoy = new LogApprovalHistory();
                $approve_log_histoy->log_id = $log_id;
                $approve_log_histoy->user_id = $user_id;
                $approve_log_histoy->role = $role;
                $approve_log_histoy->approval_managers_level = $level; // Added for aprroval levels 31Aug2018
                $approve_log_histoy->approval_date = date('Y-m-d');
                $approve_log_histoy->signature_id = $signatureId;
                $approve_log_histoy->approval_status = $status;
                if ($status == 0) {
                    $approve_log_histoy->reason_for_reject = $reason;
                } else {
                    $approve_log_histoy->reason_for_reject = 0;
                }
                $approve_log_histoy->save();
                if ($approve_log) {
                    $approve_log->log_id = $log_id;
                    $approve_log->user_id = $user_id;
                    $approve_log->role = $role;
                    $approve_log->approval_managers_level = $level; // Added for aprroval levels 31Aug2018
                    $approve_log->approval_date = date('Y-m-d');
                    $approve_log->signature_id = $signatureId;
                    $approve_log->approval_status = $status;
                    $approve_log->signatureTimeZone = $timeZone;
                    if ($status == 0) {
                        $approve_log->reason_for_reject = $reason;
                    } else {
                        $approve_log->reason_for_reject = 0;
                    }
                    $approve_log->save();
                } else {
                    $approve_log_new = new LogApproval();
                    $approve_log_new->log_id = $log_id;
                    $approve_log_new->user_id = $user_id;
                    $approve_log_new->role = $role;
                    $approve_log_new->approval_managers_level = $level; // Added for aprroval levels 31Aug2018
                    $approve_log_new->approval_date = date('Y-m-d');
                    $approve_log_new->signature_id = $signatureId;
                    $approve_log_new->approval_status = $status;
                    $approve_log_new->signatureTimeZone = $timeZone;
                    if ($status == 0) {
                        $approve_log_new->reason_for_reject = $reason;
                    } else {
                        $approve_log_new->reason_for_reject = 0;
                    }
                    $approve_log_new->save();

                    self::update_time_to_approve($agreement_details->id, $log_id, $level);
                    // UpdateTimeToApprove::dispatch($agreement_details->id, $log_id, $level);
                }

                // final level approval check 27 Aug 2018
                if ($agreement_details->default_to_agreement != 0) {
                    $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->get();
                } else {
                    $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $agreement_details->contract_id)->where("is_deleted", "=", '0')->get();
                }
                if ($level == count($approval_levels) && $status == 1) {
                    $physician_log = PhysicianLog::find($log_id);
                    $physician_log->signature = $signatureId;
                    $physician_log->approval_date = date('Y-m-d');
                    $physician_log->approved_by = $user_id;
                    $physician_log->approving_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                    $physician_log->signatureTimeZone = $timeZone;
                    $physician_log->next_approver_level = 0;
                    $physician_log->next_approver_user = 0;
                    $physician_log->save();
                } elseif ($role != 1 && $status == 0) {
                    // LogApproval::where('log_id', '=', $log_id)->where("role", "<=", $role)->update(array("approval_status" => $status));
                    LogApproval::where('log_id', '=', $log_id)->update(array("approval_status" => $status));
                    $physician_log = PhysicianLog::find($log_id);
                    $physician_log->next_approver_level = 0;
                    $physician_log->next_approver_user = 0;
                    $physician_log->save();
                } else {
                    // next level approval check 25 Dec 2018
                    if ($agreement_details->default_to_agreement != 0) {
                        $next_level_approver = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", '0')->where("is_deleted", "=", '0')->where("level", "=", $level + 1)->first();
                    } else {
                        $next_level_approver = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $agreement_details->contract_id)->where("is_deleted", "=", '0')->where("level", "=", $level + 1)->first();
                    }
                    // Log::Info('Inside Physician Log', array($next_level_approver->level));
                    $physician_log = PhysicianLog::find($log_id);
                    $physician_log->next_approver_level = $next_level_approver->level;
                    $physician_log->next_approver_user = $next_level_approver->user_id;
                    $physician_log->save();
                }
                /*if ($role == 2 && $status == 0) {
                    LogApproval::where('log_id', '=', $log_id)->where("role", "<=", $role)->update(array("approval_status" => $status));
                }*/
            }
        } else {
            $physician_log = PhysicianLog::find($log_id);
            $physician_log->signature = $signatureId;
            $physician_log->approval_date = date('Y-m-d');
            $physician_log->approved_by = $user_id;
            $physician_log->approving_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
            $physician_log->signatureTimeZone = $timeZone;
            $physician_log->save();
        }

        $log_obj_with_details = PhysicianLog::select('physician_logs.id as log_id', 'physician_logs.date', 'physician_logs.physician_id', 'physician_logs.contract_id', 'contracts.contract_name_id', 'physician_logs.practice_id', 'contracts.agreement_id', 'agreements.hospital_id')
            ->join('contracts', 'contracts.id', 'physician_logs.contract_id')
            ->join('agreements', 'agreements.id', 'contracts.agreement_id')
            ->where('physician_logs.id', '=', $log_id)
            ->first();

        if ($log_obj_with_details) {
            // PaymentStatusDashboard::updatePaymentStatusDashboard($log_obj_with_details->physician_id, $log_obj_with_details->practice_id, $log_obj_with_details->contract_id, $log_obj_with_details->contract_name_id, $log_obj_with_details->hospital_id, $log_obj_with_details->agreement_id, $log_obj_with_details->date);
            UpdatePaymentStatusDashboard::dispatch($log_obj_with_details->physician_id, $log_obj_with_details->practice_id, $log_obj_with_details->contract_id, $log_obj_with_details->contract_name_id, $log_obj_with_details->hospital_id, $log_obj_with_details->agreement_id, $log_obj_with_details->date);
        }

        return 1;
    }

    public static function update_time_to_approve($agreement_id, $log_id, $level)
    {
        // Below code is added for updating the time_to_approve columns in physician_logs table.
        $agreement_data = Agreement::getAgreementData($agreement_id);
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $physician_log = PhysicianLog::find($log_id);

        if ($level == 0) {
            $start_index = 0;
            foreach ($res_pay_frequency['date_range_with_start_end_date'] as $key => $start_end_date_obj) {
                if (strtotime($physician_log->date) >= strtotime($start_end_date_obj['start_date']) && strtotime($physician_log->date) <= strtotime($start_end_date_obj['end_date'])) {
                    $start_index = $key + 1;
                }
            }
            $counting_period_start_date = $res_pay_frequency['date_range_with_start_end_date'][$start_index]['start_date'];
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 0)->first();

            if ($approve_log_self) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($counting_period_start_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_physician = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 1) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 0)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 1)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_1 = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 2) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 1)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 2)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_2 = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 3) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 2)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 3)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_3 = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 4) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 3)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 4)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_4 = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 5) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 4)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 5)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_5 = (int)$total_days;
                $physician_log->save();
            }
        } else if ($level == 6) {
            $approve_log = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 5)->first();
            $approve_log_self = LogApproval::where('log_id', '=', $log_id)->where('approval_managers_level', '=', 6)->first();

            if (($approve_log_self) && ($approve_log)) {
                $datediff = strtotime($approve_log_self->approval_date) - strtotime($approve_log->approval_date);
                $total_days = round($datediff / (60 * 60 * 24));
                if ((int)$total_days < 0) {
                    $total_days = 0;
                }
                $physician_log->time_to_approve_by_level_6 = (int)$total_days;
                $physician_log->save();
            }
        }

        return 1;
    }

    public static function next_approver_send_email($log_ids)
    {
        $log_details = [];
        $approvers = User::select('users.*')
            ->join('physician_logs', 'physician_logs.next_approver_user', '=', 'users.id')
            ->whereIn('physician_logs.id', $log_ids)
            ->where('physician_logs.signature', '=', 0)
            ->where('physician_logs.approval_date', '=', '0000-00-00')
            ->where('physician_logs.approved_by', '=', 0)
            ->where('physician_logs.next_approver_user', '>', 0)
            ->distinct()
            ->get();

        foreach ($approvers as $approver) {
            $contracts = PhysicianLog::select('contracts.id as contract_id', 'contract_names.name as contract_name', 'physicians.id as physician_id', 'physicians.first_name as physician_first_name', 'physicians.last_name as physician_last_name')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->where('physician_logs.signature', '=', 0)
                ->where('physician_logs.approval_date', '=', '0000-00-00')
                ->where('physician_logs.approved_by', '=', 0)
                ->whereIn('physician_logs.id', $log_ids)
                ->where('physician_logs.next_approver_user', '=', $approver->id)
                ->whereNull('contracts.deleted_at')
                ->whereNull('agreements.deleted_at')
                ->where('contracts.manual_contract_valid_upto', '>=', date('Y-m-d'))
                ->where('contracts.archived', '=', 0)
                ->where('agreements.archived', '=', 0)
                ->distinct()
                ->get();

            $log_details = [];
            foreach ($contracts as $contract) {
                $logs = PhysicianLog::select(DB::raw('physician_logs.date as log_date, YEAR(physician_logs.date) as year, MONTH(physician_logs.date) as month, MONTHNAME(physician_logs.date) as month_name'))
                    ->whereIn('physician_logs.id', $log_ids)
                    ->where('physician_logs.signature', '=', 0)
                    ->where('physician_logs.approval_date', '=', '0000-00-00')
                    ->where('physician_logs.approved_by', '=', 0)
                    ->where('physician_logs.contract_id', '=', $contract->contract_id)
                    ->where('physician_logs.next_approver_user', '=', $approver->id)
                    ->groupBy('month')
                    ->groupBy('year')
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get();

                if (count($logs) > 0) {
                    foreach ($logs as $log) {
                        $log_details[] = [
                            'physician_name' => $contract->physician_first_name . ' ' . $contract->physician_last_name,
                            'contract_name' => $contract->contract_name,
                            'period' => $log->month_name . ' ' . $log->year
                        ];
                    }
                }
            }
            if (count($log_details) > 0) {
                $data = [
                    'name' => $approver->first_name,
                    'email' => $approver->email,
                    'type' => EmailSetup::NEXT_APPROVER_PENDING_LOG,
                    'with' => [
                        'name' => $approver->first_name,
                        'log_details' => $log_details
                    ]
                ];

                try {
                    EmailQueueService::sendEmail($data);
                } catch (Exception $e) {
                    log::error('Next Approver Send Email: ' . $e->getMessage());
                }
            }
        }
    }

    // Rejection Rate – Overall

    public static function final_approver_invoice_reminder_recipients_send_email($log_ids)
    {
        $agreements = PhysicianLog::select('agreements.*')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->whereIn('physician_logs.id', $log_ids)
            ->where('physician_logs.signature', '>', 0)
            ->where('physician_logs.approval_date', '!=', '0000-00-00')
            ->where('physician_logs.approved_by', '>', 0)
            ->distinct()
            ->get();

        if (count($agreements) > 0) {
            foreach ($agreements as $agreement) {
                if ($agreement) {
                    if ($agreement['invoice_reminder_recipient_1_opt_in_email'] > 0 && $agreement['invoice_reminder_recipient_1'] > 0) {
                        $user = User::select('*')
                            ->where('users.id', '=', $agreement['invoice_reminder_recipient_1'])
                            ->first();

                        $data = [
                            'email' => $user['email'],
                            'name' => $user['first_name'] . ' ' . $user['last_name'],
                            'type' => EmailSetup::REPORT_REMINDER_MAIL,
                            'with' => [
                                'name' => $user['first_name'] . ' ' . $user['last_name']
                            ]
                        ];

                        EmailQueueService::sendEmail($data);
                    }

                    if ($agreement['invoice_reminder_recipient_2_opt_in_email'] > 0 && $agreement['invoice_reminder_recipient_2'] > 0) {
                        $user = User::select('*')
                            ->where('users.id', '=', $agreement['invoice_reminder_recipient_2'])
                            ->first();

                        $data = [
                            'email' => $user['email'],
                            'name' => $user['first_name'] . ' ' . $user['last_name'],
                            'type' => EmailSetup::REPORT_REMINDER_MAIL,
                            'with' => [
                                'name' => $user['first_name'] . ' ' . $user['last_name']
                            ]
                        ];

                        try {
                            EmailQueueService::sendEmail($data);
                        } catch (Exception $e) {
                            log::error('Final Approver Invoice Reminder Recipients Send Email: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    // Rejection Rate – Provider

    public function approveLogsDashboard($physician_id, $contract_id, $signature_id, $type, $date_selector = "All")
    {
        return $this->approveLogDashboard($physician_id, $contract_id, $signature_id, $type, $date_selector);
    }

    // Rejection Rate – Contract Type

    private function approveLogDashboard($physician_id, $contract_id, $signature_id, $type, $date_selector = "All")
    {
        $contract = Contract::findOrFail($contract_id);
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $agreement_details = Agreement::select('agreements.*', 'contracts.id as contract_id', 'contracts.default_to_agreement as default_to_agreement')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->where('agreements.id', '=', $agreement_data->id)
            ->first();

        if ($agreement_details->approval_process == 1) {
            if ($contract->payment_type_id == PaymentType::PSA && $contract->wrvu_payments == false) {
                $approval_levels = [];
            } else {
                if ($agreement_details->default_to_agreement != 0) {
                    $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                } else {
                    $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $contract_id)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                }
            }
        } else {
            $approval_levels = [];
        }

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month")); // Commented by akash to get the prior month date based on payment frequency.
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        // log::info('$prior_month_end_date', array($prior_month_end_date));

        $logs_for_approve = $contract->logs()
            ->select("physician_logs.id", "physician_logs.date")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->orderBy("date", "desc")
            ->get();

        $log_ids = array();
        $role = LogApproval::physician;
        if ($type == "practice") {
            $physician = Physician::findOrFail($physician_id);
            $user = user::select("*")->where("email", "=", $physician->email)->first();
        } else {
            $user = Auth::user();
        }
        $status = 1;
        $rejected = array();
        $reason = 0;
        $signature = Signature::where("signature_id", "=", $signature_id)->first();

        foreach ($logs_for_approve as $log_id) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log_id->id)->orderBy('created_at', 'desc')->get();

            // This line of code is added for the payment frequency filter on physician dashboard for approve logs.
            $temp_range_start_date = '';
            $temp_range_end_date = '';
            if ($date_selector != "All") {
                $temp_date_selector_arr = explode(" - ", $date_selector);
                $temp_range_start_date = str_replace("-", "/", $temp_date_selector_arr[0]);
                $temp_range_end_date = str_replace("-", "/", $temp_date_selector_arr[1]);
            }

            if ((count($approval) < 1) && ($date_selector == "All" || (strtotime($log_id->date) >= strtotime($temp_range_start_date) && strtotime($log_id->date) <= strtotime($temp_range_end_date)))) {
                $log_ids[] = $log_id->id;
            }
        }

        $logArray = $this->getApprovedLogsDetails($log_ids);

        /*
         * Preventing emails being sent for deleted, archived, not valid contracts for physicians.
         * */
        if (!$contract->deleted_at || !$contract->archived || $contract->manual_contract_valid_upto <= date('Y-m-d')
            || !$agreement_details->deleted_at || !$agreement_details->archived) {
            if (count($logArray) > 0) {
                if ($logArray[0]['physician_opt_in_email'] > 0) {
                    $data = [
                        "email" => $logArray[0]['physician_email'],
                        "name" => $logArray[0]['physician_first_name'] . ' ' . $logArray[0]['physician_last_name'],
                        "logArray" => $logArray,
                        'type' => EmailSetup::PHYSICIAN_LOG_APPROVAL,
                        'with' => [
                            'logArray' => $logArray
                        ]
                    ];
                    try {
                        EmailQueueService::sendEmail($data);
                    } catch (Exception $e) {

                    }
                }
            }
        }

        return $this->approveLog($log_ids, $rejected, $signature, $role, $user->id, $status, $reason, $contract->payment_type_id);
    }

    // Rejection Rate – Practice

    public function getApprovedLogsDetails($log_ids)
    {
        return $this->getApprovedLogsDetail($log_ids);
    }

    // Rejection Rate – Reason

    private function getApprovedLogsDetail($log_ids)
    {
        $results = array();
        $logs = self::select("physician_logs.*")->wherein('physician_logs.id', $log_ids)->get();
        foreach ($logs as $log) {
            // $practice_info = DB::table('physician_practice_history')
            //     ->select('physician_practice_history.*','practices.name','practices.npi as practice_npi','practices.specialty_id as practice_specialty')
            //     ->join("practices", "practices.id", "=", "physician_practice_history.practice_id")
            //     ->where('physician_practice_history.physician_id', '=', $log->physician_id)
            //     ->where('physician_practice_history.practice_id', '=', $log->practice_id)
            //     ->first();

            //Physician to multiple hospital by 1254 : added for signature approve error

            $practice_info = DB::table('physician_practices')
                ->select('physician_practices.*', 'practices.name', 'practices.npi as practice_npi', 'practices.specialty_id as practice_specialty')
                ->join("practices", "practices.id", "=", "physician_practices.practice_id")
                ->where('physician_practices.physician_id', '=', $log->physician_id)
                ->where('physician_practices.practice_id', '=', $log->practice_id)
                ->first();
            $contract_info = DB::table('contracts')
                ->select('contracts.*', 'contract_names.name')
                ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                ->where('contracts.id', '=', $log->contract_id)
                ->first();
            $contract = Contract::findOrFail($contract_info->id);
            $practice = Practice::findOrFail($log->practice_id);
            $action_custome_name = DB::table('on_call_activities')
                ->select('on_call_activities.*')
                ->where('contract_id', '=', $contract->id)
                ->where('action_id', '=', $log->action->id)
                ->first();
            if ($practice_info->practice_specialty != null || $practice_info->practice_specialty != 0) {
                $practice_specialty = $practice->specialty->name;
            } else {
                $practice_specialty = '';
            }
            $approval_by = '';
            $approving_user_type = '';
            $reason_for_reject = '';
            $approval_date = '00/00/0000';
            $approval_status = 'Pending for approval';
            $approval = LogApproval::where("log_id", "=", $log->id)->get();
            if (count($approval) > 0) {
                foreach ($approval as $date_approve) {
                    $approval_status = 'Pending for approval';
                    if ($date_approve->role == LogApproval::physician) {
                        if ($date_approve->approval_status == 1) {
                            $approving_user_type = 'physician';
                            $approval_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                            $Physician_approve_date = format_date($date_approve->updated_at);
                            $approval_date = $Physician_approve_date;
                        } else {
                            $Physician_approve_date = 'Pending';
                        }
                    } elseif ($date_approve->role == LogApproval::contract_manager) {
                        if ($date_approve->approval_status == 1) {
                            $approving_user_type = 'contract manager';
                            $approval_by = $agreement_contract_manager;
                            $CM_approve_date = format_date($date_approve->updated_at);
                            $approval_date = $CM_approve_date;
                        } else {
                            if (count($approval) == 2 && $Physician_approve_date === 'Pending') {
                                $CM_approve_date = 'Rejected';
                                $approval_status = "Rejected by contract manager";
                                if ($date_approve->reason_for_reject != 0) {
                                    $reasons = DB::table('rejected_log_reasons')->select('*')->where("id", "=", $date_approve->reason_for_reject)->first();
                                    $reason_for_reject = $reasons->reason;
                                }
                            } else {
                                $CM_approve_date = 'Pending';
                            }
                        }
                    } elseif ($date_approve->role == LogApproval::financial_manager) {
                        if ($date_approve->approval_status == 1) {
                            $FM_approve_date = format_date($date_approve->updated_at);
                            $approval_status = "Approved";
                            $approving_user_type = 'financial manager';
                            $approval_by = $agreement_financial_manager;
                            $approval_date = $FM_approve_date;
                        } else {
                            if (count($approval) == 3 && $Physician_approve_date === 'Pending') {
                                $FM_approve_date = 'Rejected';
                                $approval_status = "Rejected by financial manager";
                                if ($date_approve->reason_for_reject != 0) {
                                    $reasons = DB::table('rejected_log_reasons')->select('*')->where("id", "=", $date_approve->reason_for_reject)->first();
                                    $reason_for_reject = $reasons->reason;
                                }
                            } else {
                                $FM_approve_date = 'Pending';
                            }
                        }
                    }
                }
            } elseif ($log->approval_date != '0000-00-00' || $log->signature != '') {
                $approval_status = 'Approved';
                if ($log->approval_date != '0000-00-00') {
                    $approval_date = format_date($log->approval_date);
                } else {
                    $approval_date = format_date($log->updated_at);
                }
                if ($log->approving_user_type == 1) {
                    $approving_user_type = 'physician';
                    $approval_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                }
            } else {
                $approval_status = 'Pending for approval';
            }
            $entered_by = '';
            $entered_by_user_type = '';
            if ($log->entered_by_user_type == 1) {
                $entered_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                $entered_by_user_type = 'physician';
            } else {
                $entered_by_user = User::withTrashed()->where('id', '=', $log->entered_by)->first();
                $entered_by = $entered_by_user->first_name . ' ' . $entered_by_user->last_name;
                $entered_by_user_type = 'practice manager';
            }

            $action_name = $log->action->name;
            if ($action_custome_name) {
                $action_name = $action_custome_name->name;
            }

            $results[] = [
                "physician_npi" => $log->physician->npi,
                "physician_first_name" => $log->physician->first_name,
                "physician_last_name" => $log->physician->last_name,
                "physician_email" => $log->physician->email,
                "physician_phone" => $log->physician->phone,
                "physician_specialty" => $log->physician->specialty->name,
                "log_id" => $log->id,
                "action" => $action_name,
                "log_date" => format_date($log->date),
                "duration" => $log->duration,
                "details" => $log->details,
                "am_pm_flag" => $log->am_pm_flag,
                "approval_status" => $approval_status,
                "approval_date" => $approval_date,
                "entered_by" => $entered_by,
                "entered_by_user_type" => $entered_by_user_type,
                "approval_by" => $approval_by,
                "approving_user_type" => $approving_user_type,
                "reason_for_reject" => $reason_for_reject,
                "created_at" => format_date($log->created_at, "m/d/Y h:i A"),
                "updated_at" => format_date($log->updated_at, "m/d/Y h:i A"),
                "practice_name" => $practice_info->name,
                "practice_npi" => $practice_info->practice_npi,
                "practice_specialty" => $practice_specialty,
                "practice_state" => $practice->state->name,
                "ptactice_start_date" => format_date($practice_info->start_date, "m/d/Y h:i A"),
                "ptactice_end_date" => format_date($practice_info->end_date, "m/d/Y h:i A"),
                "contract_name" => $contract_info->name,
                "physician_opt_in_email" => $contract_info->physician_opt_in_email,
                "contract" => $contract,
                "physician_id" => $log->physician->id
            ];
        }
        return $results;
    }

    // Rejection Rate – Approver

    public function rejectedLogs($contract_id, $physician_id)
    {
        return $this->rejectedLog($contract_id, $physician_id);
    }

    // Average Duration of Payment Approval

    private function rejectedLog($contract_id, $physician_id)
    {
        $contract = Contract::findOrFail($contract_id);
        $contract_actions = Action::getActions($contract);
        $agreement_data = Agreement::getAgreementData($contract->agreement);

//        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }

        $recent_logs = $this
            ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.contract_id", "=", $contract_id)
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->orderBy("date", "desc")
            ->distinct()
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        $entered_by_user_arr = []; // Maintains the key value pair for users to increase performance
        $rejected_by_array = [];
        $reason_arr = [];

        foreach ($recent_logs as $log) {

            //if ($contract->contract_type_id == ContractType::ON_CALL) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        $duration_data = "Full Day";
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else {
                $duration_data = formatNumber($log->duration);
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $entered_by = $log->physician->last_name . ', ' . $log->physician->first_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    if (array_key_exists($log->entered_by, $entered_by_user_arr)) {
                        $entered_by = $entered_by_user_arr[$log->entered_by];
                    } else {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name, users.id"))
                            ->where('id', '=', $log->entered_by)->first();
                        $entered_by = $user->full_name;
                        $entered_by_user_arr[$user->id] = $user->full_name;
                    }
                }
            }

            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->log_id)->orderBy('created_at', 'desc')->first();

            if (!array_key_exists($approval->user_id, $rejected_by_array)) {
                $approver_user = User::select(DB::raw('CONCAT(users.first_name, " ", users.last_name) AS approver_name, users.id'))->where("id", "=", $approval->user_id)->first();
                $rejected_by_array[$approver_user->id] = $approver_user->approver_name;
            }

            if ($approval->role == 0 && $approval->approval_status == 0) {
                $rejectedBy = $rejected_by_array[$approval->user_id];
            } else {
                $rejectedBy = 'Self';
            }

            if (array_key_exists($approval->reason_for_reject, $reason_arr)) {
                $reason_text = $reason_arr[$approval->reason_for_reject];
            } else {
                $reason = DB::table('rejected_log_reasons')->select('*')->where("id", "=", $approval->reason_for_reject)->first();
                if ($reason) {
                    $reason_text = $reason->reason;
                    $reason_arr[$reason->id] = $reason->reason;
                } else {
                    $reason_text = "";
                }
            }

            if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */
            if ($log->role == 1 && $log->approval_status == 0) {
                $results[] = [
                    "id" => $log->log_id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                    "contract_type" => $contract->contract_type_id,
                    "payment_type" => $contract->payment_type_id,/*check for payment type*/
                    "mandate" => $contract->mandate_details,
                    "actions" => $contract_actions,
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "action_id" => $log->action->id,
                    "custom_action" => $log->action->name,
                    "rejectedBy" => $rejectedBy,
                    "reason" => $reason_text,
                    "shift" => $log->am_pm_flag,
                    //call-coverage-duration  by 1254 :added partial hours
                    "partial_hours" => $contract->partial_hours,
                    "partial_hours_calculation" => $contract->partial_hours_calculation,
                    "start_time" => date('g:i A', strtotime($log->start_time)),
                    "end_time" => date('g:i A', strtotime($log->end_time))
                ];
            }
        }
        //physician to multiple hospital by 1254 : avoid repeated logs issue
        $logresult = array_unique($results, SORT_REGULAR);

        return ($logresult);
    }

    // Average Duration of Provider Approval

    public function reSubmitEditLog($post)
    {
        return $this->reSubmitUpdateLog($post[0]);
    }

    private function reSubmitUpdateLog($post)
    {
        $user_id = Auth::user()->id;

        $response = array();

        if (isset($post["log_id"]) && $post["log_id"] > 0) {
            $logpresent = self::where('id', '=', $post["log_id"])
                ->get();

            if (!isset($post["payment_type"])) {

                if ($logpresent) {
                    $thislog = $this->findOrFail($post["log_id"]);
                    $contractPresent = Contract::findOrFail($thislog->contract_id);
                    $post["payment_type"] = $contractPresent->payment_type_id;

                }
            }

            if (isset($post["action"]) && $post["action"] == -1) {

                $action = new Action;
                $action->name = $post["custom_action"];
                $action->contract_type_id = $post["contract_type"];
                $action->payment_type_id = $post["payment_type"];
                $action->action_type_id = 5;
                $action->save();

                //$physician->actions()->attach($action->id);
                $actionId = $action->id;

            } elseif (isset($post["action"]) && $post["action"] > 0) {
                $actionId = $post["action"];

            } else {

                return Response::json([
                    "status" => 0,
                    "message" => "Please select action."
                ]);
            }


            if ($logpresent) {
                $log = $this->findOrFail($post["log_id"]);

                $contract = Contract::findOrFail($log->contract_id);
                //edit action valdiation added for  burdern_on_call = false by 1254

                $start_time = "";
                $end_time = "";
                if (isset($post['start_time']) && isset($post['end_time'])) {
                    if ($post["start_time"] != "" && $post["end_time"] != "") {
                        $start_time = new DateTime($log->date . " " . $post["start_time"]);
                        $end_time = new DateTime($log->date . " " . $post["end_time"]);

                        if ($start_time >= $end_time) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Start time should be less than end time."
                            ]);
                        }

                        $check_log = PhysicianLog::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                            ->where('id', '=', $log->id)
                            ->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time)
                            ->count();

                        if ($check_log == 0) {
                            $logs = PhysicianLog::select('*')
                                ->where('contract_id', '=', $log->contract_id)
                                // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                ->where('date', '=', mysql_date($log->date))
                                ->where('id', '!=', $log->id)
                                ->where('start_time', '!=', "0000-00-00 00:00:00")
                                ->where('end_time', '!=', "0000-00-00 00:00:00")
                                ->get();

                            if (count($logs) > 0) {
                                foreach ($logs as $logs) {
                                    $date = new DateTime($logs->start_time);
                                    $logs_start_time = $date;   // ->format('Y/m/d G:i:s');

                                    $date = new DateTime($logs->end_time);
                                    $logs_end_time = $date;     // ->format('Y/m/d G:i:s');

                                    $n_start_time = $start_time;    // ->format('Y/m/d G:i:s');
                                    $n_end_time = $end_time;        // ->format('Y/m/d G:i:s');

                                    if ($n_start_time <= $logs_start_time && $n_end_time <= $logs_start_time) {
                                        // false
                                    } else if ($n_start_time >= $logs_end_time) {
                                        // false
                                    } else if ($n_start_time >= $logs_start_time && $n_start_time <= $logs_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    } else if ($n_end_time >= $logs_start_time && $n_end_time <= $logs_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    } else if ($logs_start_time >= $n_start_time && $logs_end_time <= $n_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                    ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where("contracts.id", "=", $contract->id)
                    ->where('hospitals_override_mandate_details.action_id', '=', $actionId)
                    ->where('hospitals_override_mandate_details.is_active', '=', 1)
                    ->count();

                //validation for partial hours 1
                if ($contract->partial_hours == 1) {


                    $action_name = Action::where("id", "=", $log->action_id)->pluck('name');
                    $post_action_name = Action::where("id", "=", $post['action'])->pluck('name');

                    if ($action_name[0] == "Weekday - HALF Day - On Call" || $action_name[0] == "Weekend - HALF Day - On Call" || $action_name[0] == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit half day activities."
                        ]);
                    } else if ($post_action_name == "Weekday - HALF Day - On Call" || $post_action_name == "Weekend - HALF Day - On Call" || $post_action_name == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit full day activity to half day activity for partial hours on."
                        ]);

                    }

                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->paymen_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $per_day_allowed_duration = $contract->partial_hours_calculation;
                    } else {
                        $per_day_allowed_duration = Contract::ALLOWED_MAX_HOURS_PER_DAY;
                    }

                    $total_duration = $this->getTotalDurationForPartialHoursOn($log->date, $post['action'], $contract->agreement_id);

                    $max_allowed_duration = $per_day_allowed_duration - $total_duration;
                    //check total duration of current  duration of edit log with other logs on that day is greater than 24 hours or not
                    if ($post['action'] == $log->action_id) {
                        $temp_log = $total_duration - $log->duration;
                        $final_total_duration = $temp_log + $post['duration'];
                    } else {
                        $final_total_duration = $total_duration + $post['duration'];
                    }

                    if ($final_total_duration > $per_day_allowed_duration && $contract->payment_type_id != PaymentType::PER_UNIT) {

                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "Total duration exceeded than $per_day_allowed_duration Hour(s). You can log max upto $max_allowed_duration Hour(s)."
                        ]);
                    }
                }
                if (($contract->on_call_process == 1 && $contract->burden_of_call == 0)) {
                    // show error msg only when changing action which has already log on same date o.w allow edit other fields
                    if ($log->action_id != $post['action']) {
                        // $contractlogs = self::where('physician_id', '=', $contract->physician_id)
                        $contractlogs = self::where('contract_id', '=', $contract->id)
                            ->where('date', '=', mysql_date($post['date']))
                            ->where('action_id', '=', $actionId)
                            ->whereNull('deleted_at')
                            ->get();

                        if (count($contractlogs) == 1) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Cannot change action."
                            ]);
                        }
                    }

                }//end edit action valdiation added for  burdern_on_call = false by 1254

                //edit action valdiation added for  burdern_on_call = true by #1254
                if (($contract->on_call_process == 1 && $contract->burden_of_call == 1)) {
                    $oldaction = Action::findOrFail($log->action_id);
                    $newaction = Action::findOrFail($post['action']);

                    if ($oldaction->name != $newaction->name) {
                        if ($newaction->name == "On-Call" || $oldaction->name == "On-Call") {

                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Cannot change action."
                            ]);
                        }
                    }
                }//end edit action valdiation added for  burdern_on_call = true by #1254

                $details = $post["details"];

                if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                    return Response::json([
                        "status" => 0,
                        "message" => Lang::get("api.mandate_details")
                    ]);
                } else {
                    //query 1254
                    // $logdata = self::where('physician_id', '=', $log->physician_id) // 6.1.1.12
                    $logdata = self::where('contract_id', '=', $log->contract_id)
                        ->where('date', '=', $log->date)
                        ->where('id', '!=', $post["log_id"])
                        ->get();

                    if ($post["duration"] > 0) {
                        //if ($post["contract_type"] != ContractType::ON_CALL) {
                        if ($post["payment_type"] != PaymentType::PER_DIEM) {
                            //query 1254
                            $log_hours = 0;
                            foreach ($logdata as $logdata) {
                                $log_hours = $log_hours + $logdata->duration;
                            }

                            $log_hours = $log_hours + $post["duration"];
                            // Sprint 6.1.14
                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
//                                if ($log_hours > 250){ // Old condition replaced with monthly max_hours.
                                if ($log_hours > $contract->max_hours) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.log_hours_full_per_unit")
                                    ]);
                                }
                            } else {
                                if ($log_hours > 24) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.log_hours_full")
                                    ]);
                                }
                            }

                            // Below changes are done based on payment frequency of agreement by akash.
                            $payment_type_factory = new PaymentFrequencyFactoryClass();
                            if ($contract->quarterly_max_hours == 1) {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
//                                $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
                            } else {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
//                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
                                $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
                            }

                            foreach ($payment_frequency_range as $index => $date_obj) {

                                if (strtotime($log->date) >= strtotime($date_obj['start_date']) && strtotime($log->date) <= strtotime($date_obj['end_date'])) {
                                    $monthStart = with(new DateTime($date_obj['start_date']));
                                    $monthEnd = with(new DateTime($date_obj['end_date']));
                                }
                            }

                            //if($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
                            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                                $contractDateBegin = date('Y-m-d', strtotime($contract->agreement->start_date));
                                $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contract->agreement->start_date)));
                                $currentMonth = date('m', strtotime($log->date));
                                $currentYear = date('Y', strtotime($log->date));
                                $startDay = date('d', strtotime($contract->agreement->start_date));
                                // $monthStart = with(new DateTime($currentYear . '-' . $currentMonth . '-' . $startDay));
                                // $monthEnd = with(clone $monthStart)->modify('+1 month')->modify('-1 day');

                                // log::info('Test', array($log->date));
                                $set = false;
                                while (!$set) {
                                    if ((date('Y-m-d', strtotime($log->date)) >= $contractDateBegin) && (date('Y-m-d', strtotime($log->date)) <= $contractDateEnd)) {
                                        $set = true;
                                    } else {
                                        $contractDateBegin = $contractDateEnd;
                                        $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contractDateBegin)));
                                        $set = false;
                                    }
                                }
                                $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                    // ->where('physician_id', '=', $log->physician_id)	// 6.1.1.12
                                    ->where('contract_id', '=', $log->contract_id)
                                    ->where('id', '!=', $post["log_id"])
                                    ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                    ->first();

                                if ($contract->allow_max_hours == '0') {
                                    if ($monthlogdata->duration != null) {
                                        if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.log_hours_full_month_per_unit")
                                                ]);
                                            } else {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.log_hours_full_month")
                                                ]);
                                            }
                                        }
                                    } elseif ($post["duration"] > $contract->max_hours) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    }
                                    $yearlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                        // ->where('physician_id', '=', $log->physician_id)	// 6.1.1.12
                                        ->where('contract_id', '=', $log->contract_id)
                                        ->where('id', '!=', $post["log_id"])
                                        ->whereBetween('date', array(mysql_date($contractDateBegin), mysql_date($contractDateEnd)))
                                        ->first();
                                    if ($yearlogdata->duration != null) {
                                        if ($yearlogdata->duration > $contract->annual_cap || $yearlogdata->duration + $post["duration"] > $contract->annual_cap) {
                                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.log_hours_full_year_per_unit")
                                                ]);
                                            } else {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.log_hours_full_year")
                                                ]);
                                            }
                                        }
                                    } elseif ($post["duration"] > $contract->annual_cap) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_year_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_year")
                                            ]);
                                        }
                                    }
                                }
                            } else if ($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY) {
                                if ($contract->quarterly_max_hours == 1) {
                                    $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                        // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                        ->where('contract_id', '=', $log->contract_id)
                                        ->where('id', '!=', $post["log_id"])
                                        ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                        ->first();

                                    if ($monthlogdata->duration != null) {
                                        if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    } elseif ($post["duration"] > $contract->max_hours) {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_month")
                                        ]);
                                    }
                                }
                            }

                        } else {
                            if (count($logdata) > 0) {

                                foreach ($logdata as $logdata) {
                                    $ampmflag = $logdata->am_pm_flag;
                                    $ampmlogdate = date("m/d/Y", strtotime($logdata->date));
                                    // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                                    if (isset($post["shift"])) {
                                        if (($ampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $ampmflag) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }

                                    // if already entered log is for am & pm & currently editing log is for full day then logs can't be saved
                                    if ($post["date"] != null) {
                                        if (($ampmlogdate == $post["date"]) && ($ampmflag > 0) && ($post["shift"] == 0)) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_already_exists")
                                            ]);
                                        }
                                    }
                                }
                            }
                            $action_name = Action::findOrFail($actionId);
                            $othersLogData = $this->getOtherLogdata($contract, $log);

                            if (count($othersLogData) > 0) {
                                foreach ($othersLogData as $othersLog) {
                                    $otherampmflag = $othersLog->am_pm_flag;
                                    if (isset($post["shift"])) {
                                        if (($otherampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log from other physician in same agreement for same type of contract is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $otherampmflag) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }
                                }
                            }
                            //commented by 1254 : edit action valdiation added for  burdern_on_call = true by #1254

                            // if ($actionId != $log->action_id) {
                            //     $prevAction = Action::findOrFail($log->action_id);
                            //     if ($prevAction->name == "On-Call" && $contract->burden_of_call == 1) {
                            //         if ($action_name->name != "On-Call") {
                            //             return Response::json([
                            //                 "status" => 0,
                            //                 "message" => Lang::get("api.on_call_action_change")
                            //             ]);
                            //         }
                            //     }
                            // }

                            $action_name_array = explode(" ", $action_name->name);
                            $logdate = date("m/d/Y", strtotime($log->date));
                            $year = date("Y", strtotime($logdate));
                            $physician = new Physician();
                            $holidays = $physician->getHolidays($year);
                            $holiday_on_off = $contract->holiday_on_off;
                            if ($action_name_array[0] === "Holiday") {
                                //if (!in_array($logdate, $holidays)) {

                                if (!in_array($logdate, $holidays) && $holiday_on_off == 0) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekday") {
                                $day = date('w', strtotime($logdate));
                                if ($day > 0 && $day < 6) {
                                    if (in_array($logdate, $holidays)) {
                                        // return Response::json([
                                        //     "status" => 0,
                                        //     "message" => Lang::get("api.resubmit_date")
                                        // ]);
                                    }
                                } else {

                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekend") {
                                $day = date('w', strtotime($logdate));


                                if ($day == 0 || $day == 6) {
                                    if (in_array($logdate, $holidays)) {
                                        // return Response::json([
                                        //     "status" => 0,
                                        //     "message" => Lang::get("api.resubmit_date")
                                        // ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            }

                            // Annual max shifts Start Sprint 6.1.17
                            if ($contract->payment_type_id == PaymentType::PER_DIEM && $contract->annual_cap > 0) {
                                $annual_max_shifts = self::annual_max_shifts($contract, $log->date, $post["duration"], $post["log_id"]);
                                if ($annual_max_shifts) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.annual_max_shifts_error")
                                    ]);
                                }
                            }
                            // Annual max shifts End Sprint 6.1.17
                        }

                        $physician_log_history = new PhysicianLogHistory();

                        $log->entered_by = $user_id;
                        if ($user_id != $log->physician_id) {
                            $log->entered_by_user_type = PhysicianLog::ENTERED_BY_USER;
                            $physician_log_history->entered_by_user_type = PhysicianLog::ENTERED_BY_USER;
                        } else {
                            $log->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                            $physician_log_history->entered_by_user_type = PhysicianLog::ENTERED_BY_PHYSICIAN;
                        }

                        if ($contract->partial_hours == 1) {
                            $log->log_hours = (1.00 / $contract->partial_hours_calculation) * $post["duration"];
                            $physician_log_history->log_hours = (1.00 / $contract->partial_hours_calculation) * $post["duration"];
                        } else {
                            $log->log_hours = $post["duration"];
                            $physician_log_history->log_hours = $post["duration"];
                        }

                        $physician_log_history->physician_log_id = $post["log_id"];
                        $physician_log_history->physician_id = $log->physician_id;
                        $physician_log_history->contract_id = $log->contract_id;
                        $physician_log_history->practice_id = $log->practice_id;
                        $physician_log_history->date = $log->date;
                        $physician_log_history->signature = 0;
                        $physician_log_history->approval_date = "0000-00-00";
                        $physician_log_history->timeZone = $log->timeZone;
                        $physician_log_history->entered_by_user_type = $log->entered_by_user_type;
                        $physician_log_history->duration = $post["duration"];
                        $physician_log_history->details = $details;
                        $physician_log_history->entered_by = $user_id;
                        $physician_log_history->am_pm_flag = $post["shift"];
                        // $physician_log_history->action_id=$actionId;
                        if ($contract->payment_type_id != PaymentType::TIME_STUDY) {
                            $physician_log_history->action_id = $actionId;
                        }
                        $physician_log_history->approved_by = $log->approved_by;
                        $physician_log_history->approving_user_type = $log->approving_user_type;
                        $physician_log_history->next_approver_level = $log->next_approver_level;
                        $physician_log_history->next_approver_user = $log->next_approver_user;
                        $physician_log_history->start_time = $start_time;;
                        $physician_log_history->end_time = $end_time;
                        $physician_log_history->save();


                        // $log->action_id = $actionId;
                        if ($contract->payment_type_id != PaymentType::TIME_STUDY) {
                            $log->action_id = $actionId;
                        }
                        $log->duration = $post["duration"];
                        $log->details = $details;
                        $log->am_pm_flag = $post["shift"];
                        $log->start_time = $start_time;
                        $log->end_time = $end_time;
                        //call-coverage : added for log_hours after updating duration on edit recent logs
                        //get physician id by 1254
                        // log::info("physicianid",array($post["physician_id"]));


                        //
                        if (!$log->save()) {
                            return Response::json([
                                "status" => 0,
                                "message" => Lang::get("api.resubmit_fail")
                            ]);
                        } else {

                            $agreement = Agreement::findOrFail($contract->agreement_id);

                            if ($agreement) {
                                UpdatePaymentStatusDashboard::dispatch($log->physician_id, $log->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $log->date);
                            }

                            return Response::json([
                                "status" => 1,
                                "message" => Lang::get("api.resubmit")
                            ]);
                        }
                    } else {
                        return Response::json([
                            "status" => 0,
                            "message" => Lang::get("api.duration")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => 0,
                    "message" => Lang::get("api.not_found_or_delete")
                ]);
            }
        } else {
            return Response::json([
                "status" => 0,
                "message" => Lang::get("api.not_found_or_delete")
            ]);
        }
    }

    private function getOtherLogdata($contract, $log)
    {
        return $logs = self::select('physician_logs.*')
            ->join("contracts", "contracts.id", "=", "physician_logs.contract_id")
            ->where("contracts.agreement_id", "=", $contract->agreement_id)
            ->where("contracts.contract_type_id", "=", $contract->contract_type_id)
            ->where("contracts.payment_type_id", "=", $contract->payment_type_id)
            ->where("physician_logs.physician_id", "!=", $log->physician_id)
            ->where("physician_logs.date", "=", mysql_date($log->date))
            ->get();
    }

    public function reSubmit($post, $user_id)
    {
        return $this->reSubmitLog($post[0], $user_id);
    }

    private function reSubmitLog($post, $user_id)
    {
        /*$user_id = Auth::user()->id;*/
        $response = array();
        if (isset($post["log_id"]) && $post["log_id"] > 0) {
            $logpresent = self::findOrFail($post["log_id"]);

            if (!isset($post["payment_type"])) {
                if ($logpresent) {
                    $contractPresent = Contract::findOrFail($logpresent->contract_id);
                    $post["payment_type"] = $contractPresent->payment_type_id;
                }
            }
            if (isset($post["action"]) && $post["action"] == -1) {
                $action = new Action;
                $action->name = $post["custom_action"];
                $action->contract_type_id = $post["contract_type"];
                $action->payment_type_id = $post["payment_type"];
                $action->action_type_id = 5;
                $action->save();

                //$physician->actions()->attach($action->id);
                $actionId = $action->id;

            } elseif (isset($post["action"]) && $post["action"] > 0) {
                $actionId = $post["action"];
            } else {
                return Response::json([
                    "status" => 0,
                    "message" => "Please select action."
                ]);
            }

            if ($logpresent) {
                $log = $logpresent;
                $contract = Contract::findOrFail($log->contract_id);

                $start_time = "";
                $end_time = "";
                if (isset($post['start_time']) && isset($post['end_time'])) {
                    if ($post["start_time"] != "" && $post["end_time"] != "") {
                        $start_time = new DateTime($log->date . " " . $post["start_time"]);
                        $end_time = new DateTime($log->date . " " . $post["end_time"]);

                        if ($start_time >= $end_time) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Start time should be less than end time."
                            ]);
                        }

                        $check_log = PhysicianLog::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                            ->where('id', '=', $log->id)
                            ->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time)
                            ->count();

                        if ($check_log == 0) {
                            $logs = PhysicianLog::select('*')
                                ->where('contract_id', '=', $log->contract_id)
                                // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                ->where('date', '=', mysql_date($log->date))
                                ->where('id', '!=', $log->id)
                                ->where('start_time', '!=', "0000-00-00 00:00:00")
                                ->where('end_time', '!=', "0000-00-00 00:00:00")
                                ->get();

                            if (count($logs) > 0) {
                                foreach ($logs as $logs) {
                                    $date = new DateTime($logs->start_time);
                                    $logs_start_time = $date;   // ->format('Y/m/d G:i:s');

                                    $date = new DateTime($logs->end_time);
                                    $logs_end_time = $date;     // ->format('Y/m/d G:i:s');

                                    $n_start_time = $start_time;    // ->format('Y/m/d G:i:s');
                                    $n_end_time = $end_time;    // ->format('Y/m/d G:i:s');

                                    if ($n_start_time <= $logs_start_time && $n_end_time <= $logs_start_time) {
                                        // false
                                    } else if ($n_start_time >= $logs_end_time) {
                                        // false
                                    } else if ($n_start_time >= $logs_start_time && $n_start_time <= $logs_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    } else if ($n_end_time >= $logs_start_time && $n_end_time <= $logs_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    } else if ($logs_start_time >= $n_start_time && $logs_end_time <= $n_end_time) {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Log is already exist between start time and end time."
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                    ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where("contracts.id", "=", $contract->id)
                    ->where('hospitals_override_mandate_details.action_id', '=', $actionId)
                    ->where('hospitals_override_mandate_details.is_active', '=', 1)
                    ->count();

                //edit action valdiation added for  burdern_on_call = false by 1254
                if (($contract->on_call_process == 1 && $contract->burden_of_call == 0)) {
                    if ($log->action_id != $post['action']) {
                        // $contractlogs = self::where('physician_id', '=', $contract->physician_id)    // 6.1.1.12
                        $contractlogs = self::where('contract_id', '=', $contract->id)
                            ->where('date', '=', mysql_date($post['date']))
                            ->where('action_id', '=', $post['action'])
                            ->whereNull('deleted_at')
                            ->get();

                        if (count($contractlogs) == 1) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Cannot change action."
                            ]);
                        }
                    }
                }//end edit action valdiation added for  burdern_on_call = false by 1254

                //edit action valdiation added for  burdern_on_call = true by 1254

                if (($contract->on_call_process == 1 && $contract->burden_of_call == 1)) {
                    $oldaction = Action::findOrFail($log->action_id);
                    $newaction = Action::findOrFail($post['action']);

                    if ($oldaction->name != $newaction->name) {
                        if ($newaction->name == "On-Call" || $oldaction->name == "On-Call") {

                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Cannot change action."
                            ]);
                        }
                    }
                }  //end edit action valdiation added for  burdern_on_call = true by 1254

                //validation for partial hours 1
                if ($contract->partial_hours == 1) {
                    $action_name = Action::where("id", "=", $log->action_id)->pluck('name');
                    $post_action_name = Action::where("id", "=", $post['action'])->pluck('name');

                    if ($action_name[0] == "Weekday - HALF Day - On Call" || $action_name[0] == "Weekend - HALF Day - On Call" || $action_name[0] == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit half day activities."
                        ]);
                    } else if ($post_action_name == "Weekday - HALF Day - On Call" || $post_action_name == "Weekend - HALF Day - On Call" || $post_action_name == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit full day activity to half day activity for partial hours on."
                        ]);

                    }

                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->paymen_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $per_day_allowed_duration = $contract->partial_hours_calculation;
                    } else {
                        $per_day_allowed_duration = Contract::ALLOWED_MAX_HOURS_PER_DAY;
                    }

                    $total_duration = $this->getTotalDurationForPartialHoursOn($log->date, $post['action'], $contract->agreement_id);
                    //check total duration of current  duration of edit log with other logs on that day is greater than 24 hours or not

                    if ($post['action'] == $log->action_id) {
                        $temp_log = $total_duration - $log->duration;
                        $final_total_duration = $temp_log + $post['duration'];
                    } else {
                        $final_total_duration = $total_duration + $post['duration'];
                    }

                    // if ($final_total_duration>24){
                    if ($final_total_duration > $per_day_allowed_duration && $contract->payment_type_id != PaymentType::PER_UNIT) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "Total duration exceeded than total per day hours."
                        ]);
                    }
                }
                $details = $post["details"];
                if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                    return Response::json([
                        "status" => 0,
                        "message" => Lang::get("api.mandate_details")
                    ]);
                } else {
                    // $logdata = self::where('physician_id', '=', $log->physician_id) // 6.1.1.12
                    $logdata = self::where('contract_id', '=', $log->contract_id)
                        ->where('date', '=', $log->date)
                        ->where('id', '!=', $post["log_id"])
                        ->get();
                    if ($post["duration"] > 0) {
                        //if ($post["contract_type"] != ContractType::ON_CALL) {
                        if ($post["payment_type"] != PaymentType::PER_DIEM && $post["payment_type"] != PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                            $log_hours = 0;
                            foreach ($logdata as $logdata) {
                                $log_hours = $log_hours + $logdata->duration;
                            }
                            $log_hours = $log_hours + $post["duration"];

                            // Sprint 6.1.14
                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
//                                if ($log_hours > 250) { // Old condition replaced with monthly max.
                                if ($log_hours > $contract->max_hours) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.log_hours_full_per_unit")
                                    ]);
                                }
                            } else {
                                if ($log_hours > 24) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.log_hours_full")
                                    ]);
                                }
                            }

                            // Below changes are done based on payment frequency of agreement by akash.
                            $payment_type_factory = new PaymentFrequencyFactoryClass();
                            if ($contract->quarterly_max_hours == 1) {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
//                                $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
                            } else {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
//                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
                                $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
                            }

                            foreach ($payment_frequency_range as $index => $date_obj) {

                                if (strtotime($log->date) >= strtotime($date_obj['start_date']) && strtotime($log->date) <= strtotime($date_obj['end_date'])) {
                                    $monthStart = with(new DateTime($date_obj['start_date']));
                                    $monthEnd = with(new DateTime($date_obj['end_date']));
                                }
                            }

                            //if($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
                            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                                $contractDateBegin = date('Y-m-d', strtotime($contract->agreement->start_date));
                                $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contract->agreement->start_date)));
                                $set = false;
                                while (!$set) {
                                    if ((date('Y-m-d', strtotime($log->date)) >= $contractDateBegin) && (date('Y-m-d', strtotime($log->date)) <= $contractDateEnd)) {
                                        $set = true;
                                    } else {
                                        $contractDateBegin = $contractDateEnd;
                                        $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contractDateBegin)));
                                        $set = false;
                                    }
                                }

                                $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                    // ->where('physician_id', '=', $log->physician_id) // 6.1.1.12
                                    ->where('contract_id', '=', $log->contract_id)
                                    ->where('id', '!=', $post["log_id"])
                                    ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                    ->first();
                                if ($monthlogdata->duration != null && $contract->allow_max_hours == 0) {
                                    if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    }
                                } elseif ($post["duration"] > $contract->max_hours && $contract->allow_max_hours == 0) {
                                    if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_month_per_unit")
                                        ]);
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_month")
                                        ]);
                                    }
                                }
                                $yearlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                    // ->where('physician_id', '=', $log->physician_id) // 6.1.1.12
                                    ->where('contract_id', '=', $log->contract_id)
                                    ->where('id', '!=', $post["log_id"])
                                    ->whereBetween('date', array(mysql_date($contractDateBegin), mysql_date($contractDateEnd)))
                                    ->first();
                                if ($yearlogdata->duration != null && $contract->allow_max_hours == 0) {
                                    if ($yearlogdata->duration > $contract->annual_cap || $yearlogdata->duration + $post["duration"] > $contract->annual_cap) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_year_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_year")
                                            ]);
                                        }
                                    }
                                } elseif ($post["duration"] > $contract->annual_cap && $contract->allow_max_hours == 0) {
                                    if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_year_per_unit")
                                        ]);
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_year")
                                        ]);
                                    }
                                }
                            } else if ($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY) {
                                if ($contract->quarterly_max_hours == 1) {
                                    $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                        // ->where('physician_id', '=', $log->physician_id) // 6.1.1.12
                                        ->where('contract_id', '=', $log->contract_id)
                                        ->where('id', '!=', $post["log_id"])
                                        ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                        ->first();

                                    if ($monthlogdata->duration != null) {
                                        if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    } elseif ($post["duration"] > $contract->max_hours) {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_month")
                                        ]);
                                    }
                                }
                            }
                        } else {
                            if (count($logdata) > 0) {
                                foreach ($logdata as $logdata) {
                                    $ampmflag = $logdata->am_pm_flag;
                                    $ampmlogdate = date("m/d/Y", strtotime($logdata->date));
                                    // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                                    if (isset($post["shift"])) {
                                        if (($ampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $ampmflag) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }

                                    // if already entered log is for am & pm & currently editing log is for full day then logs can't be saved
                                    if ($post["date"] != null) {
                                        if (($ampmlogdate == $post["date"]) && ($ampmflag > 0) && ($post["shift"] == 0)) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_already_exists")
                                            ]);
                                        }
                                    }
                                }
                            }
                            $action_name = Action::findOrFail($actionId);
                            $othersLogData = $this->getOtherLogdata($contract, $log);

                            if (count($othersLogData) > 0) {
                                foreach ($othersLogData as $othersLog) {
                                    $otherampmflag = $othersLog->am_pm_flag;
                                    if (isset($post["shift"])) {
                                        if (($otherampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log from other physician in same agreement for same type of contract is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $otherampmflag) {
                                                return Response::json([
                                                    "status" => 0,
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }
                                }
                            }

                            $action_name_array = explode(" ", $action_name->name);
                            $logdate = date("m/d/Y", strtotime($log->date));
                            $year = date("Y", strtotime($logdate));
                            $physician = new Physician();
                            $holidays = $physician->getHolidays($year);
                            $holiday_on_off = $contract->holiday_on_off;
                            if ($action_name_array[0] === "Holiday") {
                                //if (!in_array($logdate, $holidays)) {
                                if (!in_array($logdate, $holidays) && $holiday_on_off == 0) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekday") {
                                $day = date('w', strtotime($logdate));
                                if ($day > 0 && $day < 6) {
                                    if (in_array($logdate, $holidays)) {
                                        // return Response::json([
                                        //     "status" => 0,
                                        //     "message" => Lang::get("api.resubmit_date")
                                        // ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekend") {
                                $day = date('w', strtotime($logdate));
                                if ($day == 0 || $day == 6) {
                                    if (in_array($logdate, $holidays)) {
                                        // return Response::json([
                                        //     "status" => 0,
                                        //     "message" => Lang::get("api.resubmit_date")
                                        // ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.resubmit_date")
                                    ]);
                                }
                            }
                            // Annual max shifts Start Sprint 6.1.17
                            if ($contract->payment_type_id == PaymentType::PER_DIEM && $contract->annual_cap > 0) {
                                $annual_max_shifts = self::annual_max_shifts($contract, $log->date, $post["duration"], $post["log_id"]);
                                if ($annual_max_shifts) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.annual_max_shifts_error")
                                    ]);
                                }
                            }
                            // Annual max shifts End Sprint 6.1.17
                        }
                        $log->date = mysql_date($post['date']);
                        // $log->action_id = $actionId;
                        if ($contract->payment_type_id != PaymentType::TIME_STUDY) {
                            $log->action_id = $actionId;
                        }
                        $log->duration = $post["duration"];
                        $log->details = $details;
                        $log->am_pm_flag = $post["shift"];
                        $log->entered_by = $user_id;// $user_id;
                        //call-coverage : calcuate log hours after updating duration on rejected log
                        // $contract->partial_hours_calculation;
                        if ($contract->partial_hours == 1) {
                            $log_hour = (1.00 / $contract->partial_hours_calculation) * $post["duration"];
                            $log->log_hours = $log_hour;
                        } else {
                            $log->log_hours = $post["duration"];
                            $log_hour = $post["duration"];
                        }
                        $log->start_time = $start_time;
                        $log->end_time = $end_time;

                        if (!$log->save()) {
                            return Response::json([
                                "status" => 0,
                                "message" => Lang::get("api.resubmit_fail")
                            ]);
                        } else {

                            // add updated log details in  Physician Log History by 1254
                            $physician_log_history = new PhysicianLogHistory();
                            $physician_log_history->physician_log_id = $post["log_id"];
                            $physician_log_history->physician_id = $log->physician_id;
                            $physician_log_history->contract_id = $log->contract_id;
                            $physician_log_history->practice_id = $log->practice_id;
                            $physician_log_history->date = $log->date;
                            $physician_log_history->signature = $log->signature;
                            $physician_log_history->approval_date = "0000-00-00";
                            $physician_log_history->timeZone = $log->timeZone;
                            $physician_log_history->entered_by_user_type = $log->entered_by_user_type;
                            $physician_log_history->duration = $post["duration"];
                            $physician_log_history->details = $details;
                            $physician_log_history->entered_by = $user_id;
                            $physician_log_history->am_pm_flag = $post["shift"];
                            // $physician_log_history->action_id=$actionId;
                            if ($contract->payment_type_id != PaymentType::TIME_STUDY) {
                                $physician_log_history->action_id = $actionId;
                            }
                            $physician_log_history->approved_by = $log->approved_by;
                            $physician_log_history->approving_user_type = $log->approving_user_type;
                            $physician_log_history->next_approver_level = $log->next_approver_level;
                            $physician_log_history->next_approver_user = $log->next_approver_user;
                            //call-coverage : calcuate log hours after updating duration on rejected log
                            $physician_log_history->log_hours = $log_hour;
                            $physician_log_history->start_time = $start_time;
                            $physician_log_history->end_time = $end_time;
                            $physician_log_history->save();

                            $role = LogApproval::physician;
                            $status = 1;
                            $reason = 0;
                            $signatureRecord = Signature::where('physician_id', '=', $log->physician_id)
                                ->orderBy("created_at", "desc")->first();
                            $physician = Physician::findOrFail($log->physician_id);
                            $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
                            $this->approveReject($log->id, $role, $user->id, $signatureRecord, $status, $reason);
                            return Response::json([
                                "status" => 1,
                                "message" => Lang::get("api.resubmit")
                            ]);
                        }
                    } else {
                        return Response::json([
                            "status" => 0,
                            "message" => Lang::get("api.duration")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => 0,
                    "message" => Lang::get("api.not_found_or_delete")
                ]);
            }
        } else {
            return Response::json([
                "status" => 0,
                "message" => Lang::get("api.not_found_or_delete")
            ]);
        }
    }

    public function reSubmitUnapproved($post)
    {
        return $this->reSubmitUnapprovedLog($post[0]);
    }

    private function reSubmitUnapprovedLog($post)
    {
        $response = array();
        if (isset($post["log_id"]) && $post["log_id"] > 0) {
            $logpresent = self::where('id', '=', $post["log_id"])
                ->get();
            if (!isset($post["payment_type"])) {
                if ($logpresent) {
                    $thislog = $this->findOrFail($post["log_id"]);
                    $contractPresent = Contract::findOrFail($thislog->contract_id);
                    $post["payment_type"] = $contractPresent->payment_type_id;
                }
            }
            if (isset($post["action"]) && $post["action"] == -1) {
                $action = new Action;
                $action->name = $post["custom_action"];
                $action->contract_type_id = $post["contract_type"];
                $action->payment_type_id = $post["payment_type"];
                $action->action_type_id = 5;
                $action->save();

                //$physician->actions()->attach($action->id);
                $actionId = $action->id;
            } elseif (isset($post["action"]) && $post["action"] > 0) {
                $actionId = $post["action"];
            } else {
                return Response::json([
                    "status" => "0",
                    "message" => "Please select action."
                ]);
            }
            if ($logpresent) {
                $log = $this->findOrFail($post["log_id"]);
                $contract = Contract::findOrFail($log->contract_id);

                $start_time = "";
                $end_time = "";
                if (isset($post['start_time']) && isset($post['end_time'])) {
                    if ($post["start_time"] != "" && $post["end_time"] != "") {
                        $start_time = new DateTime($log->date . " " . $post["start_time"]);
                        $end_time = new DateTime($log->date . " " . $post["end_time"]);

                        if ($start_time >= $end_time) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => "Start time should be less than end time."
                            ]);
                        }

                        $check_log = PhysicianLog::select('*')
                            ->where('contract_id', '=', $log->contract_id)
                            // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                            ->where('id', '=', $log->id)
                            ->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time)
                            ->count();

                        if ($check_log == 0) {
                            $logs = PhysicianLog::select('*')
                                ->where('contract_id', '=', $log->contract_id)
                                // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                ->where('date', '=', mysql_date($log->date))
                                ->where('id', '!=', $log->id)
                                ->where('start_time', '!=', "0000-00-00 00:00:00")
                                ->where('end_time', '!=', "0000-00-00 00:00:00")
                                ->get();

                            foreach ($logs as $logs) {
                                $date = new DateTime($logs->start_time);
                                $logs_start_time = $date;   // ->format('Y/m/d G:i:s');
                                $date = new DateTime($logs->end_time);
                                $logs_end_time = $date;     // ->format('Y/m/d G:i:s');

                                $n_start_time = $start_time;    // ->format('Y/m/d G:i:s');
                                $n_end_time = $end_time;    // ->format('Y/m/d G:i:s');

                                if ($n_start_time <= $logs_start_time && $n_end_time <= $logs_start_time) {
                                    // false
                                } else if ($n_start_time >= $logs_end_time) {
                                    // false
                                } else if ($n_start_time >= $logs_start_time && $n_start_time <= $logs_end_time) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => "Log is already exist between start time and end time."
                                    ]);
                                } else if ($n_end_time >= $logs_start_time && $n_end_time <= $logs_end_time) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => "Log is already exist between start time and end time."
                                    ]);
                                } else if ($logs_start_time >= $n_start_time && $logs_end_time <= $n_end_time) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => "Log is already exist between start time and end time."
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Override mandate details
                $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                    ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where("contracts.id", "=", $contract->id)
                    ->where('hospitals_override_mandate_details.action_id', '=', $actionId)
                    ->where('hospitals_override_mandate_details.is_active', '=', 1)
                    ->distinct()
                    ->count();

                //validation for partial hours 1
                if ($contract->partial_hours == 1) {
                    $action_name = Action::where("id", "=", $log->action_id)->pluck('name');
                    $post_action_name = Action::where("id", "=", $post['action'])->pluck('name');

                    if ($action_name[0] == "Weekday - HALF Day - On Call" || $action_name[0] == "Weekend - HALF Day - On Call" || $action_name[0] == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit half day activities."
                        ]);
                    } else if ($post_action_name == "Weekday - HALF Day - On Call" || $post_action_name == "Weekend - HALF Day - On Call" || $post_action_name == "Holiday - HALF Day - On Call") {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "You can not edit full day activity to half day activity for partial hours on."
                        ]);

                    }

                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->paymen_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $per_day_allowed_duration = $contract->partial_hours_calculation;
                    } else {
                        $per_day_allowed_duration = Contract::ALLOWED_MAX_HOURS_PER_DAY;
                    }

                    $total_duration = $this->getTotalDurationForPartialHoursOn($log->date, $post['action'], $contract->agreement_id);

                    $max_allowed_duration = $per_day_allowed_duration - $total_duration;
                    //check total duration of current  duration of edit log with other logs on that day is greater than 24 hours or not

                    if ($post['action'] == $log->action_id) {
                        $temp_log = $total_duration - $log->duration;
                        $final_total_duration = $temp_log + $post['duration'];
                    } else {
                        $final_total_duration = $total_duration + $post['duration'];
                    }

                    if ($final_total_duration > $per_day_allowed_duration && $contract->payment_type_id != PaymentType::PER_UNIT) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => "Total duration exceeded than $contract->partial_hours_calculation Hour(s). You can log max upto $max_allowed_duration Hour(s)."
                        ]);
                    }
                }

                if (($contract->on_call_process == 1 && $contract->burden_of_call == 0)) {
                    // show error msg only when changing action, which has already log on same date o.w allow edit other fields
                    if ($log->action_id != $post['action']) {
                        // $contractlogs = self::where('physician_id', '=', $contract->physician_id)    // 6.1.1.12
                        $contractlogs = self::where('contract_id', '=', $contract->id)
                            ->where('date', '=', mysql_date($post['date']))
                            ->where('action_id', '=', $post['action'])
                            ->whereNull('deleted_at')
                            ->get();

                        if (count($contractlogs) == 1) {
                            return Response::json([
                                "status" => "0",
                                "message" => "Cannot change action."
                            ]);
                        }
                    }
                }

                //edit action valdiation added for  burdern_on_call = true by #1254
                if (($contract->on_call_process == 1 && $contract->burden_of_call == 1)) {
                    $oldaction = Action::findOrFail($log->action_id);
                    $newaction = Action::findOrFail($post['action']);

                    if ($oldaction->name != $newaction->name) {
                        if ($newaction->name == "On-Call" || $oldaction->name == "On-Call") {

                            return Response::json([
                                "status" => "0",
                                "message" => "Cannot change action."
                            ]);
                        }
                    }
                }//end edit action valdiation added for  burdern_on_call = true by #1254

                $details = $post["details"];
                if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                    return Response::json([
                        "status" => "0",
                        "message" => Lang::get("api.mandate_details")
                    ]);
                } else {
                    // $logdata = self::where('physician_id', '=', $log->physician_id)  // 6.1.1.12
                    $logdata = self::where('contract_id', '=', $log->contract_id)
                        ->where('date', '=', $log->date)
                        ->where('id', '!=', $post["log_id"])
                        ->get();
                    //Log::info("LOg data ==>", array($logdata));

                    if ($post["duration"] > 0) {
                        //if ($post["contract_type"] != ContractType::ON_CALL) {
                        if ($post["payment_type"] != PaymentType::PER_DIEM) {
                            $log_hours = 0;
                            foreach ($logdata as $logdata) {
                                $log_hours = $log_hours + $logdata->duration;
                            }
                            $log_hours = $log_hours + $post["duration"];
                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {    // Sprint 6.1.14
//                                if ($log_hours > 250){ // Old condition replaced with monthly max.
                                if ($log_hours > $contract->max_hours) {
                                    return Response::json([
                                        "status" => 0,
                                        "message" => Lang::get("api.log_hours_full_per_unit")
                                    ]);
                                }
                            } else {
                                if ($log_hours > 24) {
                                    return Response::json([
                                        "status" => "0",
                                        "message" => Lang::get("api.log_hours_full")
                                    ]);
                                }
                            }

                            // Below changes are done based on payment frequency of agreement by akash.
                            $payment_type_factory = new PaymentFrequencyFactoryClass();
                            if ($contract->quarterly_max_hours == 1) {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
                            } else {
                                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement);
//                                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
                                $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
                            }

                            foreach ($payment_frequency_range as $index => $date_obj) {

                                if (strtotime($log->date) >= strtotime($date_obj['start_date']) && strtotime($log->date) <= strtotime($date_obj['end_date'])) {
                                    $monthStart = with(new DateTime($date_obj['start_date']));
                                    $monthEnd = with(new DateTime($date_obj['end_date']));
                                }
                            }

                            //if($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
                            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {     // Sprint 6.1.14
                                $contractDateBegin = date('Y-m-d', strtotime($contract->agreement->start_date));
                                $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contract->agreement->start_date)));
                                $currentMonth = date('m', strtotime($log->date));
                                $currentYear = date('Y', strtotime($log->date));
                                $startDay = date('d', strtotime($contract->agreement->start_date));
//                                $monthStart = with(new DateTime($currentYear . '-' . $currentMonth . '-' . $startDay));
//                                $monthEnd = with(clone $monthStart)->modify('+1 month')->modify('-1 day');
                                $set = false;
                                while (!$set) {
                                    if ((date('Y-m-d', strtotime($log->date)) >= $contractDateBegin) && (date('Y-m-d', strtotime($log->date)) <= $contractDateEnd)) {
                                        $set = true;
                                    } else {
                                        $contractDateBegin = $contractDateEnd;
                                        $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contractDateBegin)));
                                        $set = false;
                                    }
                                }
                                $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                    // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                    ->where('contract_id', '=', $log->contract_id)
                                    ->where('id', '!=', $post["log_id"])
                                    ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                    ->first();
                                if ($monthlogdata->duration != null) {
                                    if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => "0",
                                                "message" => Lang::get("api.log_hours_full_month_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => "0",
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    }
                                } elseif ($post["duration"] > $contract->max_hours) {
                                    if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.log_hours_full_month_per_unit")
                                        ]);
                                    } else {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.log_hours_full_month")
                                        ]);
                                    }
                                }
                                $yearlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                    // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                    ->where('contract_id', '=', $log->contract_id)
                                    ->where('id', '!=', $post["log_id"])
                                    ->whereBetween('date', array(mysql_date($contractDateBegin), mysql_date($contractDateEnd)))
                                    ->first();
                                if ($yearlogdata->duration != null) {
                                    if ($yearlogdata->duration > $contract->annual_cap || $yearlogdata->duration + $post["duration"] > $contract->annual_cap) {
                                        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                            return Response::json([
                                                "status" => "0",
                                                "message" => Lang::get("api.log_hours_full_year_per_unit")
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => "0",
                                                "message" => Lang::get("api.log_hours_full_year")
                                            ]);
                                        }
                                    }
                                } elseif ($post["duration"] > $contract->annual_cap) {
                                    if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.log_hours_full_year_per_unit")
                                        ]);
                                    } else {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.log_hours_full_year")
                                        ]);
                                    }
                                }
                            } else if ($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY) {
                                if ($contract->quarterly_max_hours == 1) {
                                    $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                                        // ->where('physician_id', '=', $log->physician_id)     // 6.1.1.12
                                        ->where('contract_id', '=', $log->contract_id)
                                        ->where('id', '!=', $post["log_id"])
                                        ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                                        ->first();

                                    if ($monthlogdata->duration != null) {
                                        if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $post["duration"] > $contract->max_hours) {
                                            return Response::json([
                                                "status" => 0,
                                                "message" => Lang::get("api.log_hours_full_month")
                                            ]);
                                        }
                                    } elseif ($post["duration"] > $contract->max_hours) {
                                        return Response::json([
                                            "status" => 0,
                                            "message" => Lang::get("api.log_hours_full_month")
                                        ]);
                                    }
                                }
                            }
                        } else {
                            if (count($logdata) > 0) {
                                foreach ($logdata as $logdata) {
                                    $ampmflag = $logdata->am_pm_flag;
                                    $ampmlogdate = date("m/d/Y", strtotime($logdata->date));
                                    // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                                    if (isset($post["shift"])) {
                                        if (($ampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $ampmflag) {
                                                return Response::json([
                                                    "status" => "0",
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }

                                    // if already entered log is for am & pm & currently editing log is for full day then logs can't be saved
                                    if ($post["date"] != null) {
                                        if (($ampmlogdate == $post["date"]) && ($ampmflag > 0) && ($post["shift"] == 0)) {
                                            return Response::json([
                                                "status" => "0",
                                                "message" => Lang::get("api.log_already_exists")
                                            ]);
                                        }
                                    }
                                }
                            }
                            $action_name = Action::findOrFail($actionId);
                            $othersLogData = $this->getOtherLogdata($contract, $log);

                            if (count($othersLogData) > 0) {
                                foreach ($othersLogData as $othersLog) {
                                    $otherampmflag = $othersLog->am_pm_flag;
                                    if (isset($post["shift"])) {
                                        if (($otherampmflag != 0) && ($post["shift"] != 0)) {
                                            //if already entered log from other physician in same agreement for same type of contract is for am & currently adding log is for pm or viceversa then logs can be saved
                                            if ($post["shift"] == $otherampmflag) {
                                                return Response::json([
                                                    "status" => "0",
                                                    "message" => Lang::get("api.exist")
                                                ]);
                                            }
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.shift")
                                        ]);
                                    }
                                }
                            }

                            if ($actionId != $log->action_id) {
                                $prevAction = Action::findOrFail($log->action_id);
                                if ($prevAction->name == "On-Call" && $contract->burden_of_call == 1) {
                                    if ($action_name->name != "On-Call") {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.on_call_action_change")
                                        ]);
                                    }
                                }
                            }

                            $action_name_array = explode(" ", $action_name->name);
                            $logdate = date("m/d/Y", strtotime($log->date));
                            $year = date("Y", strtotime($logdate));
                            $physician = new Physician();
                            $holidays = $physician->getHolidays($year);
                            $holiday_on_off = $contract->holiday_on_off;
                            if ($action_name_array[0] === "Holiday") {
                                //if (!in_array($logdate, $holidays)) {
                                if (!in_array($logdate, $holidays) && $holiday_on_off == 0) {
                                    return Response::json([
                                        "status" => "0",
                                        "message" => Lang::get("api.resubmit_unapproved_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekday") {
                                $day = date('w', strtotime($logdate));
                                if ($day > 0 && $day < 6) {
                                    if (in_array($logdate, $holidays)) {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.resubmit_unapproved_date")
                                        ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => "0",
                                        "message" => Lang::get("api.resubmit_unapproved_date")
                                    ]);
                                }
                            } else if ($action_name_array[0] === "Weekend") {
                                $day = date('w', strtotime($logdate));
                                if ($day == 0 || $day == 6) {
                                    if (in_array($logdate, $holidays)) {
                                        return Response::json([
                                            "status" => "0",
                                            "message" => Lang::get("api.resubmit_unapproved_date")
                                        ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => "0",
                                        "message" => Lang::get("api.resubmit_unapproved_date")
                                    ]);
                                }
                            }
                        }
                        if ($contract->partial_hours == 1) {
                            $log_hour = (1.00 / $contract->partial_hours_calculation) * $post["duration"];
                            $log->log_hours = $log_hour;
                        } else {
                            $log_hour = $post["duration"];
                            $log->log_hours = $log_hour;
                        }

                        // Annual max shifts Start Sprint 6.1.17
                        if ($contract->payment_type_id == PaymentType::PER_DIEM && $contract->annual_cap > 0) {
                            $annual_max_shifts = self::annual_max_shifts($contract, $log->date, $post["duration"], $post["log_id"]);
                            if ($annual_max_shifts) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.annual_max_shifts_error")
                                ]);
                            }
                        }
                        // Annual max shifts End Sprint 6.1.17

                        $log->action_id = $actionId;
                        $log->duration = $post["duration"];
                        $log->details = $details;
                        $log->log_hours = $log_hour;
                        $log->am_pm_flag = $post["shift"];
                        $log->start_time = $start_time;
                        $log->end_time = $end_time;
                        if (!$log->save()) {
                            return Response::json([
                                "status" => "0",
                                "message" => Lang::get("api.resubmit_unapproved_fail")
                            ]);
                        } else {
                            //insert new entry in physician log history table for updated log
                            $physician_log_history = new PhysicianLogHistory();
                            $physician_log_history->physician_log_id = $post["log_id"];
                            $physician_log_history->physician_id = $log->physician_id;
                            $physician_log_history->contract_id = $log->contract_id;
                            $physician_log_history->practice_id = $log->practice_id;
                            $physician_log_history->date = $log->date;
                            $physician_log_history->signature = 0;
                            $physician_log_history->approval_date = "0000-00-00";
                            $physician_log_history->timeZone = $log->timeZone;
                            $physician_log_history->entered_by_user_type = $log->entered_by_user_type;
                            $physician_log_history->duration = $post["duration"];
                            $physician_log_history->details = $details;
                            $physician_log_history->entered_by = $log->physician_id;
                            $physician_log_history->am_pm_flag = $post["shift"];
                            $physician_log_history->action_id = $actionId;
                            $physician_log_history->approved_by = $log->approved_by;
                            $physician_log_history->approving_user_type = $log->approving_user_type;
                            $physician_log_history->next_approver_level = $log->next_approver_level;
                            $physician_log_history->next_approver_user = $log->next_approver_user;
                            $physician_log_history->log_hours = $log_hour;
                            $physician_log_history->start_time = $start_time;
                            $physician_log_history->end_time = $end_time;
                            $physician_log_history->save();


                            $status = $post["from_activity"];
                            $status .= ":";
                            $status .= $post["selected_index"];
                            return Response::json([
                                "status" => $status,
                                "message" => Lang::get("api.resubmit_unapproved")
                            ]);
                        }
                    } else {
                        return Response::json([
                            "status" => "0",
                            "message" => Lang::get("api.duration")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => "0",
                    "message" => Lang::get("api.not_found_or_delete")
                ]);
            }
        } else {
            return Response::json([
                "status" => "0",
                "message" => Lang::get("api.not_found_or_delete")
            ]);
        }
    }

    public function logReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone, $report_type = 'log')
    {
        return $this->physicianLogReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone, $report_type);
    }

    private function physicianLogReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone, $report_type)
    {
        $result = [];
        $i = 0;
        $j = 0;
        $agreement_obj = new Agreement();
        foreach ($agreement_ids as $agreement_id) {
            $agreement = Agreement::findOrFail($agreement_id);
            foreach ($physician_ids as $physician_id) {
                $agreement_data_start = $agreement_obj->getAgreementData($agreement);
                $start_month_data = $agreement_data_start->months[$months_start[$i]];
                $startmonth = strtotime(format_date($start_month_data->start_date));
                $term = $agreement_data_start->term;
                $agreement_start_end_date_range = $agreement_data_start->months;
                $agreement_data_end = $agreement_obj->getAgreementData($agreement);
                $end_month_data = $agreement_data_end->months[$months_end[$i]];
                $endmonth = strtotime(format_date($end_month_data->end_date));
                $contracts = $this->getContractforReport($startmonth, $endmonth, $agreement_id, $physician_id, $contract_type);
                if (count($contracts) > 0) {
                    $practices = PhysicianPracticeHistory::where("physician_id", "=", $physician_id)->orderBy("start_date")->get();
                    foreach ($contracts as $contract) {
                        //Get managers for contract 3sep2018
                        // $managers= ApprovalManagerInfo::select("agreement_approval_managers_info.*","approval_managers_type.manager_type as role")
                        //     ->join("approval_managers_type","approval_managers_type.approval_manager_type_id","=","agreement_approval_managers_info.type_id")
                        //     ->where("agreement_approval_managers_info.agreement_id","=",$agreement_id)
                        //     ->where("agreement_approval_managers_info.is_deleted","=",'0');
                        $managers = ApprovalManagerInfo::select("agreement_approval_managers_info.*", "users.title as role")
                            ->join("users", "users.id", "=", "agreement_approval_managers_info.user_id")
                            ->where("agreement_approval_managers_info.agreement_id", "=", $agreement_id)
                            ->where("agreement_approval_managers_info.is_deleted", "=", '0');
                        if ($contract->default_to_agreement == 0) {
                            $managers = $managers->where("agreement_approval_managers_info.contract_id", "=", $contract->contract_id);
                        } else {
                            $managers = $managers->where("agreement_approval_managers_info.contract_id", "=", 0);
                        }
                        $managers = $managers->orderBy("agreement_approval_managers_info.level")->get();
                        $contract_data = [];
                        foreach ($practices as $practice) {
                            // $start=strtotime(date('m/01/Y', $startmonth));
                            $start = strtotime(date('m/d/Y', $startmonth));
                            if ($contract->manual_contract_end_date != "0000-00-00") {
                                $manual_contract_end_date = strtotime(format_date($contract->manual_contract_end_date));
                                if ($report_type != 'status' && $report_type != 'log') {
                                    $end = strtotime(date('m/t/Y', $manual_contract_end_date));
                                } else {
                                    // $end = strtotime(date('m/t/Y', $endmonth));
                                    $end = strtotime(date('m/d/Y', $endmonth));
                                }
                            } else {
                                // $end = strtotime(date('m/t/Y', $endmonth));
                                $end = strtotime(date('m/d/Y', $endmonth));
                            }
                            // Below code is added for payment frequency feature based on ranges by akash
                            for ($range_start = $months_start[$i]; $range_start <= $months_end[$i]; $range_start++) {
                                $range_obj = $agreement_start_end_date_range[$range_start];

                                $month_start_date = $range_obj->start_date;
                                $month_end_date = $range_obj->end_date;

                                if (strtotime($practice->end_date) >= strtotime($month_start_date) && (strtotime($practice->start_date) <= strtotime($month_start_date) || (strtotime($practice->start_date) > strtotime($month_start_date) && strtotime($practice->start_date) <= strtotime($month_end_date)))) {
                                    if ($report_type != 'log') {
                                        $logs = $this->getApprovedStatus($contract, $agreement_id, $practice, $month_start_date, $month_end_date, $physician_id, $term);
                                    } else {
                                        $logs = $this->getApprovedLogs($contract, $agreement_id, $practice, $month_start_date, $month_end_date, $physician_id, $term);
                                    }
                                    //  Log::info('physician=>> '.$physician_id,array(count($logs)));
                                    if (count($logs) > 0) {
                                        $contract_data[] = $logs;
                                    }
                                }
                            }

                            // while ($start < $end) {
                            //     $month_start_date = date('m/01/Y', $start);
                            //     $month_end_date = date('m/t/Y', $start);
                            //     $start = strtotime("+1 month", $start);
                            //     if(strtotime($practice->end_date) >= strtotime($month_start_date) && (strtotime($practice->start_date) <= strtotime($month_start_date) || (strtotime($practice->start_date) > strtotime($month_start_date) && strtotime($practice->start_date) <= strtotime($month_end_date)))) {
                            //         if($report_type != 'log') {
                            //             $logs = $this->getApprovedStatus($contract, $agreement_id, $practice, $month_start_date, $month_end_date, $physician_id, $term);
                            //         }else{
                            //             $logs = $this->getApprovedLogs($contract, $agreement_id, $practice, $month_start_date, $month_end_date, $physician_id, $term);
                            //         }
                            //       //  Log::info('physician=>> '.$physician_id,array(count($logs)));
                            //         if (count($logs) > 0) {
                            //             $contract_data[] = $logs;
                            //         }
                            //     }
                            // }
                        }
                        if (count($contract_data) > 0) {
                            $result[$j]["data"] = [
                                "Period" => format_date($start_month_data->start_date) . " - " . format_date($end_month_data->end_date),
                                "agreement_name" => $agreement->name,
                                "agreement_start_date" => $agreement->start_date,
                                "agreement_end_date" => $agreement->end_date,
                                "managers" => $managers,
                                "localtimeZone" => $localtimeZone
                            ];
                            $result[$j]["logs"] = $contract_data;
                            $j++;
                        }
                    }
                }
            }
            $i++;
        }
        return $result;
    }

    private function getContractforReport($start_date, $end_date, $agreementId, $physician, $contract_type)
    {
        $start_date = mysql_date(date('m/d/Y', $start_date));
        $end_date = mysql_date(date('m/d/Y', $end_date));

        $query = DB::table('physician_logs')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
            DB::raw("physician_practice_history.first_name as first_name"),
            DB::raw("physician_practice_history.last_name as last_name"),
            DB::raw("physician_practice_history.physician_id as physician_id "),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.contract_type_id as contract_type_id"),
            DB::raw("contracts.payment_type_id as payment_type_id"),
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contract_types.name as contract_name"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.default_to_agreement as default_to_agreement"),
            DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
            DB::raw("sum(physician_logs.duration) as worked_hours"),
            DB::raw("agreements.start_date as start_date"),
            DB::raw("agreements.end_date as end_date"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("physician_practice_history.start_date as practice_start_date"),
            DB::raw("physician_practice_history.end_date as practice_end_date")

        )
            ->join("physician_practice_history", "physician_practice_history.physician_id", "=", "physician_logs.physician_id")
            ->join("practices", "practices.id", "=", "physician_practice_history.practice_id")
            ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
            ->join("contracts", function ($join) {
                $join->on("contracts.id", "=", "physician_logs.contract_id")
                    ->on("contracts.agreement_id", "=", "agreements.id");
            })
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->where("contracts.agreement_id", "=", $agreementId)
            ->where("physician_logs.physician_id", "=", $physician)
            ->whereBetween("physician_logs.date", [$start_date, $end_date]);

        if ($contract_type != -1) {
            $contracts = $query->where("contracts.contract_type_id", "=", $contract_type)->groupBy("physician_logs.contract_id")->get();
        } else {
            $contracts = $query->groupBy("physician_logs.contract_id ")->get();
        }

        return $contracts;
    }

    private function getApprovedStatus($contract, $agreementId, $practice, $startmonth, $endmonth, $physician_id, $term)
    {
        $practiceName = Practice::withTrashed()->findOrFail($practice->practice_id);
        $contract_names = ContractName::join("contracts", "contract_names.id", "=", "contracts.contract_name_id")
                                    ->where("contracts.id", "=", $contract->contract_id)
                                    ->pluck('name');
        $sum_worked_hours = 0;
        $contract_data = [
            "physician_name" => "{$contract->last_name}, {$contract->first_name}",
            // "physician_id" => $contract->physician_id,   // 6.1.1.12
            "physician_id" => $physician_id,
            "practice_id" => $practice->practice_id,
            "practice_name" => $practiceName->name,
            "contract_id" => $contract->contract_id,
            "payment_type_id" => $contract->payment_type_id,
            "date_range" => "{$startmonth} - {$endmonth}",
            "expected_hours" => $contract->expected_hours,
            "max_hours" => $contract->max_hours * $term,
            "worked_hours" => $contract->worked_hours,
            "contract_name" => $contract->contract_name,
            "agreement_name" => $contract->agreement_name,
            "agreement_id" => $agreementId,
            "physician" => $physician_id,
            "agreement_start_date" => date('m/d/Y', strtotime($contract->start_date)),
            "agreement_end_date" => date('m/d/Y', strtotime($contract->end_date)),
            "practice_start_date" => $practice->start_date,
            "practice_end_date" => $practice->end_date,
            "sum_worked_hour" => 0,
            "sum_max_hours" => $contract->max_hours,
            "breakdown" => [],
            "contract_names" => $contract_names,
        ];

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->contract_id)->pluck("name", "action_id")->toArray(); //toArray method added to get the array response as expected below.
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $physician = Physician::withTrashed()->findOrFail($physician_id);

        $logs = DB::table("physician_logs")->select(
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"),
            DB::raw("actions.name as action"),
            DB::raw("actions.action_type_id as action_type_id"),
            DB::raw("physician_logs.date as date"),
            DB::raw("physician_logs.approval_date as approval_date"),
            DB::raw("physician_logs.updated_at as updated_at"),
            DB::raw("physician_logs.updated_at as updated_at"),
            DB::raw("physician_logs.signature as signature"),
            DB::raw("physician_logs.duration as worked_hours"),
            DB::raw("physician_logs.details as notes"),
            DB::raw("physician_logs.entered_by as entered_by"),
            DB::raw("physician_logs.entered_by_user_type as entered_by_user_type"),
            DB::raw("physician_logs.physician_id as physician_id")
        )
            ->join("actions", "actions.id", "=", "physician_logs.action_id");
        //added for soft delete
        if ($physician->deleted_at != Null) {
            $logs = $logs->join("physicians", function ($join) {
                $join->on("physicians.id", "=", "physician_logs.physician_id")
                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
            });
        } else {
            $logs = $logs->where('physician_logs.deleted_at', '=', Null);
        }
        $logs = $logs->where("physician_logs.contract_id", "=", $contract->contract_id)
            ->where("physician_logs.practice_id", "=", $practice->practice_id)
            // ->where("physician_logs.physician_id", "=", $contract->physician_id)     // 6.1.1.12
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->whereBetween("physician_logs.date", [mysql_date($startmonth), mysql_date($endmonth)])
            ->orderBy("physician_logs.date", "asc")
            ->get();

        foreach ($logs as $log) {
            if ($log->action_type_id == 3) $log->action = "Custom: Activity";
            if ($log->action_type_id == 4) $log->action = "Custom: Mgmt Duty";
            if ($log->approval_date != "0000-00-00") {
                $approve_date = date('m/d/Y', strtotime($log->approval_date));
            } else if ($log->approval_date == "0000-00-00" && $log->signature > 0) {
                $approve_date = date('m/d/Y', strtotime($log->updated_at));
            } else {
                $approve_date = "";
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $entered_by = $user->full_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->entered_by)->first();
                    $entered_by = $user->full_name;
                }
            }

            $Physician_approve_date = "Pending";
            $CM_approve_date = "Pending";
            $FM_approve_date = "Pending";
            $EM_approve_date = "Pending";
            $approval_status = "Pending";
            $Physician_approve_signature = "";
            $CM_approve_signature = "";
            $FM_approve_signature = "";
            $EM_approve_signature = "";
            $mgr_approve_date = "Pending";
            $mgr_signature = "";
            $mgr_approve_signature = "";
            $CM_name = "NA";
            $FM_name = "NA";
            $EM_name = "NA";
            $log_approve_info = array();
            $pending_log_approve_info = array();
            $approval_dates = LogApproval::where("log_id", "=", $log->log_id)->orderBy("approval_managers_level")->get();
            //Log::info("approobbbbbb===>>>>>", array($approval_dates));

            $startmonth = date("Y/m/d", strtotime($startmonth));
            $endmonth = date("Y/m/d", strtotime($endmonth));
            $amount_paids = DB::table('amount_paid_physicians')->where("physician_id","=",$contract->physician_id)
                           ->where("start_date","=",$startmonth)
                           ->where("end_date","=",$endmonth)
                           ->get();

            if(count($amount_paids)>0){
              foreach ($amount_paids as $amount_paid){
                $payment_approval_date = format_date($amount_paid->created_at);
              }
             }
             else{
                 $payment_approval_date = "";
             }

            if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $oncallactivity = DB::table('on_call_activities')
                    ->select('name', 'contract_id', 'action_id')
                    ->where("contract_id", "=", $contract->contract_id)
                    ->where("action_id", "=", $log->action_id)
                    ->first();
                if ($oncallactivity) {
                    $log->action = $oncallactivity->name;
                }
            }

            $pending_log_approve_info[0]=[
                'role' => LogApproval::physician,
                'date' => "",
                'name' => $contract->last_name.','.$contract->first_name,
              ];

              $contracts_data = Contract::where("id","=",$contract->contract_id)->get();
              foreach ($contracts_data as $contracts){
                  $approval_managers = ApprovalManagerInfo::where("contract_id","=",$contract->contract_id)->orWhere("contract_id","=",0)->where("agreement_id","=",$contracts->agreement_id)->get();
              }

            foreach ($approval_managers as $approval_manager){
                $approval_manage = User::where("id","=",$approval_manager->user_id)->first();
                $pending_log_approve_info[$approval_manager->level]=[
                    'role' => $approval_manager->level,
                    'date' => "",
                    'name' => $approval_manage->last_name.','.$approval_manage->first_name,
                  ];

            }

            if ($approve_date != "") {
                $approval_status = "Approved";
//                log::info('$approval_dates', array($approval_dates));
                if (count($approval_dates) > 1) {
                    foreach ($approval_dates as $date_approve) {
                        if ($date_approve->role == LogApproval::physician) {
                            $Physician_approve_date = $date_approve->updated_at;
                            $Physician_signature = Signature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($Physician_signature) {
                                $Physician_approve_signature = $Physician_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::physician,
                                'name' => $contract->last_name . ',' . $contract->first_name,
                                'date' => format_date($Physician_approve_date),
                                'approve_signature' => $Physician_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::contract_manager) {
                            $CM_approve_date = $date_approve->updated_at;
                            $CM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($CM_signature) {
                                $CM_approve_signature = $CM_signature->signature_path;
                            }
                            $cm_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($cm_user) {
                                $CM_name = $cm_user->last_name . ',' . $cm_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::contract_manager,
                                'name' => $CM_name,
                                'date' => format_date($CM_approve_date),
                                'approve_signature' => $CM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::financial_manager) {
                            $FM_approve_date = $date_approve->updated_at;
                            $FM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($FM_signature) {
                                $FM_approve_signature = $FM_signature->signature_path;
                            }
                            $fm_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($fm_user) {
                                $FM_name = $fm_user->last_name . ',' . $fm_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::financial_manager,
                                'name' => $FM_name,
                                'date' => format_date($FM_approve_date),
                                'approve_signature' => $FM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::executive_manager) {
                            $EM_approve_date = $date_approve->approval_date;
                            $EM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($EM_signature) {
                                $EM_approve_signature = $EM_signature->signature_path;
                            }
                            $em_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($em_user) {
                                $EM_name = $em_user->last_name . ',' . $em_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::executive_manager,
                                'name' => $EM_name,
                                'date' => format_date($EM_approve_date),
                                'approve_signature' => $EM_approve_signature
                            ];
                        } else {
                            // This block is added for skipping approval title.
                            if( $date_approve->approval_managers_level == 0) {
                               $Physician_approve_date = $date_approve->updated_at; // This line is added if logs are approved by practice manager on behalf of physician.
                            }
                            $mgr_approve_date = $date_approve->approval_date;
                            $mgr_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($mgr_signature) {
                                $mgr_approve_signature = $mgr_signature->signature_path;
                            }
                            $mgr_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($mgr_user) {
                                $mgr_name = $mgr_user->last_name . ',' . $mgr_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => $date_approve->role,
                                'name' => $mgr_name,
                                'date' => format_date($mgr_approve_date),
                                'approve_signature' => $mgr_approve_signature
                            ];
                        }
                    }
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => format_date($Physician_approve_date),
                        "payment_approval_date" => $payment_approval_date,
                        "pending_log_approval_info" => $pending_log_approve_info,
                        /*"CM_approve_date" => format_date($CM_approve_date),
                        "FM_approve_date" => format_date($FM_approve_date),*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => $Physician_approve_signature,
                        "CM_approve_signature" => $CM_approve_signature,
                        "FM_approve_signature" => $FM_approve_signature,
                        "CM_name" => $CM_name,
                        "FM_name" => $FM_name,
                        "approval_status" => $approval_status,
                        "entered_by" => $entered_by
                    ];
                } else {
                    $Physician_signature = Signature::where("signature_id", "=", $log->signature)->first();
                    $approval_status = "Approved";
                    if ($Physician_signature) {
                        $Physician_approve_signature = $Physician_signature->signature_path;
                    }
                    $Physician_approve_date = format_date($log->updated_at);
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => $Physician_approve_date,
                        "payment_approval_date" => $payment_approval_date,
                        "pending_log_approval_info" => $pending_log_approve_info,
                        /*"CM_approve_date" => "NA",
                        "FM_approve_date" => "NA",*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => $Physician_approve_signature,
                        "CM_approve_signature" => "NA",
                        "FM_approve_signature" => "NA",
                        "CM_name" => $CM_name,
                        "FM_name" => $FM_name,
                        "approval_status" => $approval_status,
                        "entered_by" => $entered_by
                    ];
                }
            } else {
                if (count($approval_dates) > 0) {
                    foreach ($approval_dates as $date_approve) {
                        if ($date_approve->role == LogApproval::physician) {
                            if ($date_approve->approval_status == 1) {
                                $Physician_approve_date = format_date($date_approve->updated_at);
                                $Physician_signature = Signature::where("signature_id", "=", $date_approve->signature_id)->first();
                                if ($Physician_signature) {
                                    $Physician_approve_signature = $Physician_signature->signature_path;
                                }
                            } else {
                                $Physician_approve_date = 'Pending';
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::physician,
                                'name' => $contract->last_name . ',' . $contract->first_name,
                                'date' => $Physician_approve_date,
                                'approve_signature' => $Physician_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::contract_manager) {
                            if ($date_approve->approval_status == 1) {
                                $CM_approve_date = format_date($date_approve->updated_at);
                                $CM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                                if ($CM_signature) {
                                    $CM_approve_signature = $CM_signature->signature_path;
                                }
                            } else {
                                if (count($approval_dates) == 2 && $Physician_approve_date === 'Pending') {
                                    $CM_approve_date = 'Rejected';
                                    $approval_status = "Rejected";
                                } else {
                                    $CM_approve_date = 'Pending';
                                }
                            }
                            $cm_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($cm_user) {
                                $CM_name = $cm_user->last_name . ',' . $cm_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::contract_manager,
                                'name' => $CM_name,
                                'date' => $CM_approve_date,
                                'approve_signature' => $CM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::financial_manager) {
                            if ($date_approve->approval_status == 1) {
                                $FM_approve_date = format_date($date_approve->updated_at);
                                // $approval_status = "Approved";
                                $FM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                                if ($FM_signature) {
                                    $FM_approve_signature = $FM_signature->signature_path;
                                }
                            } else {
                                if (count($approval_dates) == 3 && $Physician_approve_date === 'Pending') {
                                    $FM_approve_date = 'Rejected';
                                    $approval_status = "Rejected";
                                } else {
                                    $FM_approve_date = 'Pending';
                                }
                            }
                            $fm_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($fm_user) {
                                $FM_name = $fm_user->last_name . ',' . $fm_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::financial_manager,
                                'name' => $FM_name,
                                'date' => $FM_approve_date,
                                'approve_signature' => $FM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::executive_manager) {
                            if ($date_approve->approval_status == 1) {
                                $EM_approve_date = format_date($date_approve->updated_at);
                                // $approval_status = "Approved";
                                $EM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                                if ($EM_signature) {
                                    $EM_approve_signature = $EM_signature->signature_path;
                                }
                            } else {
                                if (count($approval_dates) == 3 && $Physician_approve_date === 'Pending') {
                                    $EM_approve_date = 'Rejected';
                                    $approval_status = "Rejected";
                                } else {
                                    $FM_approve_date = 'Pending';
                                }
                            }
                            $em_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($em_user) {
                                $EM_name = $em_user->last_name . ',' . $em_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::executive_manager,
                                'name' => $EM_name,
                                'date' => $EM_approve_date,
                                'approve_signature' => $EM_approve_signature
                            ];
                        } else {
                            // This block is added for skipping approval title.
                            $mgr_approve_date = $date_approve->approval_date;
                            $mgr_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($mgr_signature) {
                                $mgr_approve_signature = $mgr_signature->signature_path;
                            }
                            $mgr_user = User::withTrashed()->where('id', '=', $date_approve->user_id)->first();
                            if ($mgr_user) {
                                $mgr_name = $mgr_user->last_name . ',' . $mgr_user->first_name;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => $date_approve->role,
                                'name' => $mgr_name,
                                'date' => format_date($mgr_approve_date),
                                'approve_signature' => $mgr_approve_signature
                            ];
                        }
                    }
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => $Physician_approve_date,
                        "payment_approval_date" => $payment_approval_date,
                        /*"CM_approve_date" => $CM_approve_date,
                        "FM_approve_date" => $FM_approve_date,*/
                        "pending_log_approval_info" => $pending_log_approve_info,
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => 'NA',
                        "CM_approve_signature" => "NA",
                        "FM_approve_signature" => "NA",
                        "CM_name" => $CM_name,
                        "FM_name" => $FM_name,
                        "approval_status" => $approval_status,
                        "entered_by" => $entered_by
                    ];
                } elseif ($contract->approval_process != 1) {
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => 'Pending',
                        "payment_approval_date" => " ",
                        "pending_log_approval_info" => $pending_log_approve_info,
                        /*"CM_approve_date" => "NA",
                        "FM_approve_date" => "NA",*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => 'NA',
                        "CM_approve_signature" => "NA",
                        "FM_approve_signature" => "NA",
                        "CM_name" => $CM_name,
                        "FM_name" => $FM_name,
                        "approval_status" => $approval_status,
                        "entered_by" => $entered_by
                    ];
                } else {
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => 'Pending',
                        "payment_approval_date" => " ",
                        "pending_log_approval_info" => $pending_log_approve_info,
                        /*"CM_approve_date" => "Pending",
                        "FM_approve_date" => "Pending",*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => 'NA',
                        "CM_approve_signature" => "NA",
                        "FM_approve_signature" => "NA",
                        "CM_name" => $CM_name,
                        "FM_name" => $FM_name,
                        "approval_status" => $approval_status,
                        "entered_by" => $entered_by
                    ];
                }
            }
            $sum_worked_hours = $sum_worked_hours + $log->worked_hours;
        }
        $contract_data["sum_worked_hour"] = $sum_worked_hours;
        if (count($contract_data["breakdown"]) > 0) {
            return $contract_data;
        } else {
            return array();
        }
    }

    private function getApprovedLogs($contract, $agreementId, $practice, $startmonth, $endmonth, $physician_id, $term)
    {
        $practiceName = Practice::withTrashed()->findOrFail($practice->practice_id);
        $sum_worked_hours = 0;
        $contract_data = [
            "physician_name" => "{$contract->last_name}, {$contract->first_name}",
            // "physician_id" => $contract->physician_id,   // 6.1.1.12
            "physician_id" => $physician_id,
            "practice_id" => $practice->practice_id,
            "practice_name" => $practiceName->name,
            "contract_id" => $contract->contract_id,
            "payment_type_id" => $contract->payment_type_id,
            "date_range" => "{$startmonth} - {$endmonth}",
            "expected_hours" => $contract->expected_hours,
            "max_hours" => $contract->max_hours * $term,
            "worked_hours" => $contract->worked_hours,
            "contract_name" => $contract->contract_name,
            "agreement_name" => $contract->agreement_name,
            "agreement_id" => $agreementId,
            "physician" => $physician_id,
            "agreement_start_date" => date('m/d/Y', strtotime($contract->start_date)),
            "agreement_end_date" => date('m/d/Y', strtotime($contract->end_date)),
            "practice_start_date" => $practice->start_date,
            "practice_end_date" => $practice->end_date,
            "sum_worked_hour" => 0,
            "sum_max_hours" => $contract->max_hours,
            "breakdown" => []
        ];

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->contract_id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $physician = Physician::withTrashed()->findOrFail($physician_id);

        $logs = DB::table("physician_logs")->select(
            DB::raw("physician_logs.id as log_id"),
            DB::raw("physician_logs.action_id as action_id"),
            DB::raw("actions.name as action"),
            DB::raw("actions.action_type_id as action_type_id"),
            DB::raw("physician_logs.date as date"),
            DB::raw("physician_logs.approval_date as approval_date"),
            DB::raw("physician_logs.updated_at as updated_at"),
            DB::raw("physician_logs.signature as signature"),
            DB::raw("physician_logs.duration as worked_hours"),
            DB::raw("physician_logs.details as notes")
        )
            ->join("actions", "actions.id", "=", "physician_logs.action_id");
        //added for soft delete
        if ($physician->deleted_at != Null) {
            $logs = $logs->join("physicians", function ($join) {
                $join->on("physicians.id", "=", "physician_logs.physician_id")
                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
            });
        } else {
            $logs = $logs->where('physician_logs.deleted_at', '=', Null);
        }
        $logs = $logs->where("physician_logs.contract_id", "=", $contract->contract_id)
            ->where("physician_logs.practice_id", "=", $practice->practice_id)
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->whereBetween("physician_logs.date", [mysql_date($startmonth), mysql_date($endmonth)])
            ->orderBy("physician_logs.date", "asc")
            ->get();

        foreach ($logs as $log) {
            if ($log->action_type_id == 3) $log->action = "Custom: Activity";
            if ($log->action_type_id == 4) $log->action = "Custom: Mgmt Duty";
            if ($log->approval_date != "0000-00-00") {
                $approve_date = date('m/d/Y', strtotime($log->approval_date));
            } else if ($log->approval_date == "0000-00-00" && $log->signature > 0) {
                $approve_date = date('m/d/Y', strtotime($log->updated_at));
            } else {
                $approve_date = "";
            }

            $Physician_approve_date = "";
            $CM_approve_date = "";
            $FM_approve_date = "";
            $EM_approve_date = "";
            $Physician_approve_signature = "";
            $CM_approve_signature = "";
            $FM_approve_signature = "";
            $EM_approve_signature = "";
            $log_approve_info = array();
            $mgr_approve_signature = "";

            // if ($log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS){
            //     //$contract = Contract::where('id', '=', $log->contract_id)->first();
            //     $uncompensated_action = Action::getActions($contract);
            //     foreach($uncompensated_action as $uncompensated){
            //         $log->action = $uncompensated["display_name"];
            //     }
            // }

            if ($approve_date != "") {
                $approval_dates = LogApproval::where("log_id", "=", $log->log_id)->orderBy("approval_managers_level")->get();
                if (count($approval_dates) > 1) {
                    foreach ($approval_dates as $date_approve) {
                        if ($date_approve->role == LogApproval::physician) {
                            $Physician_approve_date = $date_approve->approval_date;
                            $Physician_signature = Signature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($Physician_signature) {
                                $Physician_approve_signature = $Physician_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::physician,
                                'date' => $Physician_approve_date,
                                'approve_signature' => $Physician_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::contract_manager) {
                            $CM_approve_date = $date_approve->approval_date;
                            $CM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($CM_signature) {
                                $CM_approve_signature = $CM_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::contract_manager,
                                'date' => format_date($CM_approve_date),
                                'approve_signature' => $CM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::financial_manager) {
                            $FM_approve_date = $date_approve->approval_date;
                            $FM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($FM_signature) {
                                $FM_approve_signature = $FM_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::financial_manager,
                                'date' => format_date($FM_approve_date),
                                'approve_signature' => $FM_approve_signature
                            ];
                        } elseif ($date_approve->role == LogApproval::executive_manager) {
                            $EM_approve_date = $date_approve->approval_date;
                            $EM_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($EM_signature) {
                                $EM_approve_signature = $EM_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => LogApproval::executive_manager,
                                'date' => format_date($EM_approve_date),
                                'approve_signature' => $EM_approve_signature
                            ];
                        } else {
                            $mgr_approve_date = $date_approve->approval_date;
                            $mgr_signature = UserSignature::where("signature_id", "=", $date_approve->signature_id)->first();
                            if ($mgr_signature) {
                                $mgr_approve_signature = $mgr_signature->signature_path;
                            }
                            $log_approve_info[$date_approve->approval_managers_level] = ['role' => $date_approve->role,
                                'date' => format_date($mgr_approve_date),
                                'approve_signature' => $mgr_approve_signature
                            ];
                        }
                    }
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => format_date($Physician_approve_date),
                        /*"CM_approve_date" => format_date($CM_approve_date),
                        "FM_approve_date" => format_date($FM_approve_date),*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => $Physician_approve_signature,
                        "CM_approve_signature" => $CM_approve_signature,
                        "FM_approve_signature" => $FM_approve_signature
                    ];
                } else {
                    $Physician_signature = Signature::where("signature_id", "=", $log->signature)->first();
                    if ($Physician_signature) {
                        $Physician_approve_signature = $Physician_signature->signature_path;
                        $log_approve_info[0] = ['role' => LogApproval::physician,
                            'date' => format_date($approve_date),
                            'approve_signature' => $Physician_approve_signature
                        ];
                    }
                    $contract_data["breakdown"][] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => format_date($approve_date),
                        /*"CM_approve_date" => "NA",
                        "FM_approve_date" => "NA",*/
                        "log_approval_info" => $log_approve_info,
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes,
                        "Physician_approve_signature" => $Physician_approve_signature,
                        "CM_approve_signature" => "NA",
                        "FM_approve_signature" => "NA"
                    ];
                }
                $sum_worked_hours = $sum_worked_hours + $log->worked_hours;
            }
        }
        $contract_data["sum_worked_hour"] = $sum_worked_hours;
        if (count($contract_data["breakdown"]) > 0) {
            return $contract_data;
        } else {
            return array();
        }
    }

    public function paymentSummarylogReportData($hospital_id, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone)
    {
        return $this->paymentSummaryReportData($hospital_id, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone);
    }

    private function paymentSummaryReportData($hospital_id, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone)
    {
        $results = [];
        $i = 0;
        $j = 0;

        if ($contract_type != -1) {
            $contract_types = ContractType::select('*')->where('id', '=', $contract_type)->get();
        } else {
            $contract_types = ContractType::select("contract_types.*")
                ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereNull("contracts.deleted_at")
                ->where("agreements.is_deleted", "=", false)
                ->where("agreements.hospital_id", "=", $hospital_id)
                ->whereNull("agreements.deleted_at")
                ->where("agreements.start_date", '<=', mysql_date(now()))
                ->where("agreements.end_date", '>=', mysql_date(now()))
                ->groupBy("contract_types.id")
                ->get();
        }

        foreach ($contract_types as $contract_type) {
            $contract_data = [];
            foreach ($physician_ids as $physician_id) {
                $all_contract = Contract::where("contracts.physician_id", "=", $physician_id)
                    ->whereIn("contracts.agreement_id", $agreement_ids)
                    ->where("contracts.contract_type_id", "=", $contract_type->id)
                    ->pluck('id')->toArray();

                $month_start_date = $months_start;
                $month_start_date = date("Y-m-d", strtotime($months_start));
                $month_end_date = $months_end;
                $month_end_date = date("Y-m-d", strtotime($month_end_date));
                $region_hospital = RegionHospitals::where("region_hospitals.hospital_id", "=", $hospital_id)->first();

                if ($region_hospital) {
                    $amount_paid_details = DB::table('amount_paid')->select(DB::raw("sum(amount_paid.amountPaid) as amountPaidTotal"), 'contracts.id as contract_id',
                        'amount_paid.start_date', 'amount_paid.end_date', 'physicians.first_name', 'physicians.last_name', 'practices.name as practice_name', 'payment_types.description',
                        'hospitals.name as hospital_name', 'health_system_regions.region_name as region_name', 'specialties.name as specialty_name');
                } else {
                    $amount_paid_details = DB::table('amount_paid')->select(DB::raw("sum(amount_paid.amountPaid) as amountPaidTotal"), 'contracts.id as contract_id',
                        'amount_paid.start_date', 'amount_paid.end_date', 'physicians.first_name', 'physicians.last_name', 'practices.name as practice_name', 'payment_types.description',
                        'hospitals.name as hospital_name', 'specialties.name as specialty_name');
                }
                $amount_paid_details = $amount_paid_details->join("physicians", "physicians.id", "=", "amount_paid.physician_id")
                    ->join("contracts", "contracts.id", "=", "amount_paid.contract_id")
                    ->join("practices", "practices.id", "=", "amount_paid.practice_id")
                    ->join("payment_types", "payment_types.id", "=", "contracts.payment_type_id")
                    ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
                    ->join("specialties", "specialties.id", "=", "physicians.specialty_id");

                if ($region_hospital) {
                    $amount_paid_details = $amount_paid_details->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id")
                        ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");
                }
                $amount_paid_details = $amount_paid_details->whereIn("amount_paid.contract_id", $all_contract)
                    ->where("physicians.id", "=", $physician_id)
                    ->where("hospitals.id", "=", $hospital_id)
                    ->where("amount_paid.amountPaid", "!=", 0)
                    //   ->where("physician_logs.approval_date","!=","0000-00-00")
                    ->where("amount_paid.start_date", ">=", $month_start_date)
                    ->where("amount_paid.end_date", "<=", $month_end_date)
                    ->where("contracts.physician_id", "=", $physician_id)
                    ->orderBy('contract_id')
                    ->groupBy('contracts.physician_id')
                    ->groupBy('contracts.practice_id')
                    ->first();

                if ($amount_paid_details && $amount_paid_details->amountPaidTotal != null) {
                    $sum_worked_hours = PhysicianLog::select(DB::raw("sum(physician_logs.duration) as sum_worked_hours"))
                        ->where("physician_logs.physician_id", "=", $physician_id)
                        ->whereIn("physician_logs.contract_id", $all_contract)
                        ->where("physician_logs.approval_date", "!=", "0000-00-00")
                        ->whereNull('physician_logs.deleted_at')
                        ->first();

                    $amount_paid_details->sum_worked_hours = $sum_worked_hours->sum_worked_hours;
                    $contract_data[] = $amount_paid_details;
                }
            }

            if (count($contract_data) > 0) {
                $results[] = [
                    "contract_type" => $contract_type->name,
                    "Period" => format_date($months_start) . " - " . format_date($months_end),
                    "agreement_name" => 'Testing 1',
                    "agreement_start_date" => '2020-10-10',
                    "agreement_end_date" => '2025-10-10',
                    "localtimeZone" => $localtimeZone,
                    "payment_detail" => $contract_data,
                ];
            }
        }
        return $results;
    }

    public function rejectedNotifymail($rejected)
    {
        return $this->rejectedNotifymailPhysicians($rejected);
    }

    private function rejectedNotifymailPhysicians($rejected)
    {
        $physicians = [];
        $physician_ids = $this->select("physician_id", "entered_by")
            ->whereIn("id", $rejected)
            ->distinct()->get();
        foreach ($physician_ids as $physician_id) {
            if ($physician_id->physician_id == $physician_id->entered_by) {
                $physician = Physician::findOrFail($physician_id->physician_id);
                if ($physician) {
                    $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->first();
                    if ($device_token) {
                        $physician->device_token = $device_token->device_token;
                        $physician->device_type = $device_token->device_type;
                    } else {
                        $physician->device_token = "";
                        $physician->device_type = "";
                    }
                    $physician->type = "physician";
                    $physicians[] = $physician;
                }
            } else {
                $physician = User::findOrFail($physician_id->entered_by);
                $physician->device_token = "";
                $physician->device_type = "";
                $physician->type = "non-physician";
                $physicians[] = $physician;
            }
        }
        return $physicians;
    }

    public function getDetails()
    {
        return $this->getDetailsForLogs();
    }

    // This function is used to fetch records from amount_paid table based on agreement_id,contract_id and physician_id      Rohit Added on 15/09/2022

    private function getDetailsForLogs()
    {

        $results = [];
        $hospitals = Hospital::all();
        foreach ($hospitals as $hospital) {
            $activeAgreements = Agreement::getActiveAgreement($hospital->id);
            //Log::info("agreements=>",array($activeAgreements));
            foreach ($activeAgreements as $agreement) {
                $agreement_contract_manager = '';
                $agreement_financial_manager = '';
                //Log::info("agree=>",array($agreement));
                if ($agreement->contract_manager != 0) {
                    $agreement_contract_manager_user = DB::table('users')->where('id', '=', $agreement->contract_manager)->first();
                    if (count($agreement_contract_manager_user) > 0) {
                        $agreement_contract_manager = $agreement_contract_manager_user->first_name . ' ' . $agreement_contract_manager_user->last_name;
                    } else {
                        $agreement_contract_manager = "User deleted";
                    }
                    $agreement_financial_manager_user = DB::table('users')->where('id', '=', $agreement->financial_manager)->first();
                    if (count($agreement_financial_manager_user) > 0) {
                        $agreement_financial_manager = $agreement_financial_manager_user->first_name . ' ' . $agreement_financial_manager_user->last_name;
                    } else {
                        $agreement_financial_manager = "User deleted";
                    }
                }
                $contracts = Contract::where("agreement_id", "=", $agreement->id)->orderBy("contract_type_id")->get();
                foreach ($contracts as $contract) {
                    //Log::info("contracts=>>", array($contract));
                    $logs = self::select("physician_logs.*")->where('physician_logs.contract_id', '=', $contract->id)->get();
                    //Log::info("logs=>", array($logs));
                    foreach ($logs as $log) {
                        $practice_info = DB::table('physician_practice_history')
                            ->select('physician_practice_history.*', 'practices.name', 'practices.npi as practice_npi', 'practices.specialty_id as practice_specialty')
                            ->join("practices", "practices.id", "=", "physician_practice_history.practice_id")
                            ->where('physician_practice_history.physician_id', '=', $log->physician_id)
                            ->where('physician_practice_history.practice_id', '=', $log->practice_id)
                            ->first();
                        $practice = Practice::findOrFail($log->practice_id);
                        if ($practice_info->practice_specialty != null || $practice_info->practice_specialty != 0) {
                            $practice_specialty = $practice->specialty->name;
                        } else {
                            $practice_specialty = '';
                        }
                        $approval_by = '';
                        $approving_user_type = '';
                        $reason_for_reject = '';
                        $approval_date = '00/00/0000';
                        $approval_status = 'Pending for approval';
                        $approval = LogApproval::where("log_id", "=", $log->id)->get();
                        if (count($approval) > 0) {
                            foreach ($approval as $date_approve) {
                                $approval_status = 'Pending for approval';
                                if ($date_approve->role == LogApproval::physician) {
                                    if ($date_approve->approval_status == 1) {
                                        $approving_user_type = 'physician';
                                        $approval_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                                        $Physician_approve_date = format_date($date_approve->updated_at);
                                        $approval_date = $Physician_approve_date;
                                    } else {
                                        $Physician_approve_date = 'Pending';
                                    }
                                } elseif ($date_approve->role == LogApproval::contract_manager) {
                                    if ($date_approve->approval_status == 1) {
                                        $approving_user_type = 'contract manager';
                                        $approval_by = $agreement_contract_manager;
                                        $CM_approve_date = format_date($date_approve->updated_at);
                                        $approval_date = $CM_approve_date;
                                    } else {
                                        if (count($approval) == 2 && $Physician_approve_date === 'Pending') {
                                            $CM_approve_date = 'Rejected';
                                            $approval_status = "Rejected by contract manager";
                                            if ($date_approve->reason_for_reject != 0) {
                                                $reasons = DB::table('rejected_log_reasons')->select('*')->where("id", "=", $date_approve->reason_for_reject)->first();
                                                $reason_for_reject = $reasons->reason;
                                            }
                                        } else {
                                            $CM_approve_date = 'Pending';
                                        }
                                    }
                                } elseif ($date_approve->role == LogApproval::financial_manager) {
                                    if ($date_approve->approval_status == 1) {
                                        $FM_approve_date = format_date($date_approve->updated_at);
                                        $approval_status = "Approved";
                                        $approving_user_type = 'financial manager';
                                        $approval_by = $agreement_financial_manager;
                                        $approval_date = $FM_approve_date;
                                    } else {
                                        if (count($approval) == 3 && $Physician_approve_date === 'Pending') {
                                            $FM_approve_date = 'Rejected';
                                            $approval_status = "Rejected by financial manager";
                                            if ($date_approve->reason_for_reject != 0) {
                                                $reasons = DB::table('rejected_log_reasons')->select('*')->where("id", "=", $date_approve->reason_for_reject)->first();
                                                $reason_for_reject = $reasons->reason;
                                            }
                                        } else {
                                            $FM_approve_date = 'Pending';
                                        }
                                    }
                                }
                            }
                        } elseif ($log->approval_date != '0000-00-00' || $log->signature != '') {
                            $approval_status = 'Approved';
                            if ($log->approval_date != '0000-00-00') {
                                $approval_date = format_date($log->approval_date);
                            } else {
                                $approval_date = format_date($log->updated_at);
                            }
                            if ($log->approving_user_type == 1) {
                                $approving_user_type = 'physician';
                                $approval_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                            }
                        } else {
                            $approval_status = 'Pending for approval';
                        }
                        $entered_by = '';
                        $entered_by_user_type = '';
                        if ($log->entered_by_user_type == 1) {
                            $entered_by = $log->physician->first_name . ' ' . $log->physician->last_name;
                            $entered_by_user_type = 'physician';
                        } else {
                            $entered_by_user = User::withTrashed()->where('id', '=', $log->entered_by)->first();
                            $entered_by = $entered_by_user->first_name . ' ' . $entered_by_user->last_name;
                            $entered_by_user_type = 'practice manager';
                        }
                        $results[] = [
                            "hospital_name" => $hospital->name,
                            "hospital_npi" => $hospital->npi,
                            "hospital_city" => $hospital->city,
                            "hospital_state" => $hospital->state->name,
                            "hospital_address" => $hospital->address,
                            "hospital_expiration" => format_date($hospital->expiration),
                            "agreement_id" => $agreement->id,
                            "agreement_name" => $agreement->name,
                            "agreement_start_date" => format_date($agreement->start_date),
                            "agreement_end_date" => format_date($agreement->end_date),
                            "agreement_contract_manager" => $agreement_contract_manager,
                            "agreement_financial_manager" => $agreement_financial_manager,
                            "send_invoice_day" => $agreement->send_invoice_day,
                            "pass1_day" => $agreement->pass1_day,
                            "pass2_day" => $agreement->pass2_day,
                            "invoice_receipient" => $agreement->invoice_receipient,
                            "approval_process" => $agreement->approval_process,
                            "contract_id" => $contract->id,
                            "contract_name" => $contract->contractName->name,
                            "contract_type" => $contract->contractType->name,
                            "payment_type" => $contract->paymentType->name,
                            "min_hours" => $contract->min_hours,
                            "max_hours" => $contract->max_hours,
                            "expected_hours" => $contract->expected_hours,
                            "rate" => $contract->rate,
                            "description" => $contract->description,
                            "weekday_rate" => $contract->weekday_rate,
                            "weekend_rate" => $contract->weekend_rate,
                            "holiday_rate" => $contract->holiday_rate,
                            "end_date" => format_date($contract->end_date),
                            "physician_npi" => $log->physician->npi,
                            "physician_first_name" => $log->physician->first_name,
                            "physician_last_name" => $log->physician->last_name,
                            "physician_email" => $log->physician->email,
                            "physician_phone" => $log->physician->phone,
                            "physician_specialty" => $log->physician->specialty->name,
                            "log_id" => $log->id,
                            "action" => $log->action->name,
                            "log_date" => format_date($log->date),
                            "duration" => $log->duration,
                            "details" => $log->details,
                            "am_pm_flag" => $log->am_pm_flag,
                            "approval_status" => $approval_status,
                            "approval_date" => $approval_date,
                            "entered_by" => $entered_by,
                            "entered_by_user_type" => $entered_by_user_type,
                            "approval_by" => $approval_by,
                            "approving_user_type" => $approving_user_type,
                            "reason_for_reject" => $reason_for_reject,
                            "created_at" => format_date($log->created_at, "m/d/Y h:i A"),
                            "updated_at" => format_date($log->updated_at, "m/d/Y h:i A"),
                            "practice_name" => $practice_info->name,
                            "practice_npi" => $practice_info->practice_npi,
                            "practice_specialty" => $practice_specialty,
                            "practice_state" => $practice->state->name,
                            "ptactice_start_date" => format_date($practice_info->start_date, "m/d/Y h:i A"),
                            "ptactice_end_date" => format_date($practice_info->end_date, "m/d/Y h:i A")
                        ];
                    }
                }
            }
        }
        //Log::info("logs=>", array($results));
        return $results;
    }

    public function getHoursCheck($contractId, $physicianId, $selected_date, $duration)
    {
        return $this->getAllHoursCheck($contractId, $physicianId, $selected_date, $duration);
    }

    private function getAllHoursCheck($contractId, $physicianId, $selected_date, $duration)
    {
        $contract = Contract::findOrFail($contractId);
        $agreement = Agreement::findOrFail($contract->agreement_id);
        // Below changes are done based on payment frequency of agreement by akash.

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
            $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
//            $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
//            $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date']; // Frequency wise start and end dates
            $payment_frequency_range = $res_pay_frequency['month_wise_start_end_date']; // Month wise start and end dates
        }

        // log::info('$payment_frequency_range', array($payment_frequency_range));
        // log::info('$contract', array($contract->agreement_id));

        /**
         * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
         */
        if ($contract->prior_start_date != '0000-00-00') {
            $contract_start_date = $contract->prior_start_date;
        } else {
            $contract_start_date = $contract->agreement->start_date;
        }

        $contractDateBegin = date('Y-m-d', strtotime($contract_start_date));
        $contractDateEnd = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contract_start_date))));
        $currentMonth = date('m', strtotime($selected_date));
        $currentYear = date('Y', strtotime($selected_date));
        $startDay = date('d', strtotime($contract->agreement->start_date));
        $monthStart = with(new DateTime($currentYear . '-' . $currentMonth . '-' . $startDay));
        $monthEnd = with(clone $monthStart)->modify('+1 month')->modify('-1 day');
        $contractFirstYearStartDate = $contractDateBegin;
        $contractFirstYearEndDate = $contractDateEnd;

        $set = false;
        while (!$set) {
            if ((date('Y-m-d', strtotime($selected_date)) >= $contractDateBegin) && (date('Y-m-d', strtotime($selected_date)) <= $contractDateEnd)) {
                $set = true;
            } else {
                $contractDateBegin = date('Y-m-d', strtotime('+1 day', strtotime($contractDateEnd)));
                $contractDateEnd = date('Y-m-d', strtotime('-1 days', strtotime('+1 years', strtotime($contractDateBegin))));
                $set = false;
            }
        }

        if ((($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
            $current_range_start_date = '';
            $current_range_end_date = '';
            foreach ($payment_frequency_range as $current_range) {
                if ((date('Y-m-d', strtotime($selected_date)) >= date('Y-m-d', strtotime($current_range['start_date']))) && (date('Y-m-d', strtotime($selected_date)) <= date('Y-m-d', strtotime($current_range['end_date'])))) {
                    $current_range_start_date = $current_range['start_date'];
                    $current_range_end_date = $current_range['end_date'];
                }
            }

            /**
             * This validation condition is for not allowing max hour log monthly/annually for the contract.
             */

            if ($contract->allow_max_hours == '0') {
                $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                    //                ->where('physician_id', '=', $physicianId)
                    ->where('contract_id', '=', $contractId)
                    ->whereNull('deleted_at')
                    // ->whereBetween('date', array(mysql_date($monthStart->format('Y-m-d')), mysql_date($monthEnd->format('Y-m-d'))))
                    ->whereBetween('date', array(mysql_date($current_range_start_date), mysql_date($current_range_end_date)))
                    ->first();
                if ($monthlogdata->duration != null) {
                    if ($monthlogdata->duration > $contract->max_hours || $monthlogdata->duration + $duration > $contract->max_hours) {
                        return "Excess monthly";
                    }
                } elseif ($duration > $contract->max_hours) {
                    return "Excess monthly";
                }

                $temp_month_start = with(new DateTime($contractDateBegin));
                $temp_month_end = with(clone $temp_month_start)->modify('+1 month')->modify('-1 day');

                $total_allowed_monthly_duration_sum = 0;

                // Calculate the actual log duration in all months in a year.
                while (strtotime($temp_month_end->format('Y-m-d')) <= strtotime($contractDateEnd)) {
                    $monthlogdata = self::select(DB::raw('SUM(duration) as duration'))
                        //                    ->where('physician_id', '=', $physicianId)
                        ->where('contract_id', '=', $contractId)
                        ->whereNull('deleted_at')
                        ->whereBetween('date', array(mysql_date($temp_month_start->format('Y-m-d')), mysql_date($temp_month_end->format('Y-m-d'))))
                        ->first();

                    if ($monthlogdata->duration != null) {
                        if ($monthlogdata->duration > $contract->max_hours) {
                            $total_allowed_monthly_duration_sum += $contract->max_hours;
                        } else {
                            $total_allowed_monthly_duration_sum += $monthlogdata->duration;
                        }
                    }

                    $temp_month_start = with(clone $temp_month_end)->modify('+1 day');
                    $temp_month_end = with(clone $temp_month_start)->modify('+1 month')->modify('-1 day');
                }

                //check condition of log date is in first year for the prior worked hours condition.
                if ((date('Y-m-d', strtotime($selected_date)) >= $contractFirstYearStartDate) && (date('Y-m-d', strtotime($selected_date)) <= $contractFirstYearEndDate)) {
                    $annual_without_prior_logs = $contract->annual_cap - $contract->prior_worked_hours;

                    if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                        if ($total_allowed_monthly_duration_sum != 0) {
                            if ($total_allowed_monthly_duration_sum > $annual_without_prior_logs || $total_allowed_monthly_duration_sum + $duration > $annual_without_prior_logs) {
                                return "Excess annual";
                            }
                        } elseif ($duration > $annual_without_prior_logs) {
                            return "Excess annual";
                        }
                    }
                } else {

                    if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                        if ($total_allowed_monthly_duration_sum != 0) {
                            if ($total_allowed_monthly_duration_sum > $contract->annual_cap || $total_allowed_monthly_duration_sum + $duration > $contract->annual_cap) {
                                return "Excess annual";
                            }
                        } elseif ($duration > $contract->annual_cap) {
                            return "Excess annual";
                        }
                    }
                }
            }

        }
        $logdata = self::select(DB::raw('SUM(duration) as duration'))
            ->where('physician_id', '=', $physicianId)
            ->where('contract_id', '=', $contractId)
            ->where('date', '=', mysql_date($selected_date))
            ->whereNull('deleted_at')
            ->get();

        if (count($logdata) > 0) {
            //if logs not yet entered , can be able to add (save) any log
            if ($contract->payment_type_id == PaymentType::PER_UNIT) {    // Sprint 6.1.14
//            if($logdata->duration > 250 || $logdata->duration + $duration > 250){ // Old condition replaced with monthly max check.
                if ($logdata[0]->duration > $contract->max_hours || $logdata[0]->duration + $duration > $contract->max_hours) {
                    return "Excess Monthly";
                } else {
                    return "Under 24";
                }
            } else {
                if ($logdata[0]->duration > 24 || $logdata[0]->duration + $duration > 24) {

                    return "Excess 24";
                } else {
                    return "Under 24";
                }
            }
        }
    }

    public function validateLogEntryForSelectedDateRange($physician_id, $contract, $contract_id, $selected_date, $action_id, $shift, $log_details, $physician, $duration, $user_id)
    {
        //some server side validation
        //fetch already entered logs for the physician, same contract, same date
        $save_flag = 1;
        $approvedLogsRange = $this->getApprovedLogsRangeAPI($contract);
        $logdate_formatted = date_parse_from_format("Y-m-d", $selected_date);
        $approved_range = [];
        foreach ($approvedLogsRange as $key => $range_obj) {
            if (strtotime($selected_date) >= strtotime($range_obj->start_date) && strtotime($selected_date) <= strtotime($range_obj->end_date)) {
                if (!array_key_exists($key, $approved_range)) {
                    $save_flag = 0;
                    break;
                }
            }
        }

        if ($save_flag == 0) {

            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => "Error"
            ]);

        }

        if ($contract->burden_of_call == 1 && $contract->on_call_process == 1) {
            // $logdata = self::where('physician_id', '=', $physician_id)
            $logdata = self::where('contract_id', '=', $contract_id)
                ->where('date', '=', mysql_date($selected_date))
                ->whereNull('deleted_at')
                ->get();

        } else {
            // $logdata = self::where('physician_id', '=', $physician_id)
            $logdata = self::where('contract_id', '=', $contract_id)
                ->where('date', '=', mysql_date($selected_date))
                ->where('action_id', '=', $action_id)
                ->whereNull('deleted_at')
                ->get();
        }

        $log_durations = self::where('contract_id', '=', $contract_id)
            ->where('date', '=', mysql_date($selected_date))
            ->where('action_id', '=', $action_id)
            ->whereNull('deleted_at')
            ->sum('physician_logs.duration');

        //call-coverage-duration  by 1254 : validation for partial_hours exceed than 24hrs
        $total_duration = $this->getTotalDurationForPartialHoursOn($selected_date, $action_id, $contract->agreement_id);
        if (($total_duration + $duration) > 24.00) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => "Error"
            ]);
        }

        // Annual max shifts Start Sprint 6.1.17
        if ($contract->payment_type_id == PaymentType::PER_DIEM && $contract->annual_cap > 0) {
            $annual_max_shifts = self::annual_max_shifts($contract, $selected_date, $duration, "");
            if ($annual_max_shifts) {
                return $annual_max_shifts;
            }
        }
        // Annual max shifts End Sprint 6.1.17
        $action_selected = Action::findOrFail($action_id);
        //if logs not yet entered , can be able to add (save) any log
        if (count($logdata) == 0) {
            //If called in and call back the not allow for no log
            if ($contract->burden_of_call == 0 || ($action_selected->name != "Called-In" && $action_selected->name != "Called-Back")) {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => "Success"
                ]);

            } else {

                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => "Error"
                ]);
            }

        } else {
            $enteredLogEligibility = 0;
            if (($contract->on_call_process == 1 && $contract->burden_of_call == 0) && (count($logdata) == 1)) {

                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => "Error"
                ]);
            }

            foreach ($logdata as $logdata) {
                $ampmflag = $logdata->am_pm_flag;
                // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                if (($ampmflag != 0) && ($shift != 0)) {
                    //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                    if ($shift != $ampmflag) {
                        /*$result= $this->saveLogs($action_id,$shift,$log_details,$physician,$contract_id,$selected_date,$duration,$user_id);
                        if($result != "Success" ){
                            return $result;
                        }*/
                        $enteredLogEligibility = 1;
                    }
                }
                //If on call then allow to enter called in and call back
                $action_present = Action::findOrFail($logdata->action_id);
                if ($contract->burden_of_call == 0 || $action_present->name == "On-Call") {
                    if ($contract->burden_of_call == 0 || $action_selected->name != "On-Call") {
                        /*$result = $this->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id);
                        if ($result != "Success") {
                            return $result;
                        }*/
                        $enteredLogEligibility = 1;
                    }
                }
            }

            if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if ($contract->partial_hours == 1) {
                    $log_durations = $log_durations + $duration;
                    if ($log_durations > $contract->partial_hours_calculation) {
                        $enteredLogEligibility = 0;
                    } else {
                        $enteredLogEligibility = 1;
                    }
                } else {
                    if ($log_durations > 0) {
                        $enteredLogEligibility = 0;
                    } else {
                        $enteredLogEligibility = 1;
                    }
                }
            } else {
                if ($contract->on_call_process == 1 && $contract->partial_hours == 0) {
                    $check_durations = self::select('physician_logs.*')
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->where('physician_logs.date', '=', mysql_date($selected_date))
                        ->where('physician_logs.action_id', '=', $action_id)
                        ->where('contracts.id', '=', $contract->agreement_id)
                        ->whereNull('physician_logs.deleted_at')
                        ->count();

                    if ($check_durations > 0) {
                        $enteredLogEligibility = 0;
                    } else {
                        $enteredLogEligibility = 1;
                    }
                }
            }

            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->on_call_process == 0 && $contract->partial_hours == 0) {
                    $contract_logs_present_on_date = self::where('contract_id', '=', $contract_id)
                        ->where('date', '=', mysql_date($selected_date))
                        ->whereNull('deleted_at')
                        ->get();

                    if (count($contract_logs_present_on_date) > 0) {
                        foreach ($contract_logs_present_on_date as $log) {
                            if ($log->am_pm_flag == 0) {
                                $enteredLogEligibility = 0;
                            } else if ($log->am_pm_flag == $shift) {
                                $enteredLogEligibility = 0;
                            }
                        }
                    }
                }
            }

            if ($enteredLogEligibility) {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => "Success"
                ]);
            } else {

                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => "Error"
                ]);
            }
        }
    }

    // Below function is added to get the current months total days/duration to display to the physician.

    public function getApprovedLogsRangeAPI($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        $approved_range = [];
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }
            if ($isApproved == true) //if($log->approval_date !='0000-00-00' || $log->signature > 0)
            {
                foreach ($agreement_data->months as $key => $range_obj) {
                    if (strtotime($log->date) >= strtotime($range_obj->start_date) && strtotime($log->date) <= strtotime($range_obj->end_date)) {
                        if (!array_key_exists($key, $approved_range)) {
                            $approved_range[$key] = $range_obj;
                        }
                    }
                }
            }
        }

        return $approved_range;
    }

    private function paymentSummaryReportData_old($hospital_id, $agreement_ids, $physician_ids, $months_start, $months_end, $contract_type, $localtimeZone)
    {
        $result = [];
        $i = 0;
        $j = 0;
        foreach ($agreement_ids as $agreement_id) {
            $agreement = Agreement::findOrFail($agreement_id);
            $contract_data = [];
            foreach ($physician_ids as $physician_id) {
                $all_contract = Contract::where("contracts.agreement_id", "=", $agreement_id)
                    ->where("contracts.physician_id", "=", $physician_id);
                if ($contract_type != -1) {
                    $all_contract = $all_contract->where("contracts.contract_type_id", "=", $contract_type);
                }
                $all_contract = $all_contract->pluck('id')->toArray();

                $month_start_date = $months_start;
                $month_start_date = date("Y-m-d", strtotime($months_start));
                $month_end_date = $months_end;
                $month_end_date = date("Y-m-d", strtotime($month_end_date));
                $region_hospital = RegionHospitals::where("region_hospitals.hospital_id", "=", $hospital_id)->first();

                if ($region_hospital) {
                    $amount_paid_details = DB::table('amount_paid')->select(DB::raw("sum(amount_paid.amountPaid) as amountPaidTotal"), 'contracts.id as contract_id',
                        'amount_paid.start_date', 'amount_paid.end_date', 'physicians.first_name', 'physicians.last_name', 'practices.name as practice_name', 'payment_types.description',
                        'hospitals.name as hospital_name', 'health_system_regions.region_name as region_name', 'specialties.name as specialty_name');
                } else {
                    $amount_paid_details = DB::table('amount_paid')->select(DB::raw("sum(amount_paid.amountPaid) as amountPaidTotal"), 'contracts.id as contract_id',
                        'amount_paid.start_date', 'amount_paid.end_date', 'physicians.first_name', 'physicians.last_name', 'practices.name as practice_name', 'payment_types.description',
                        'hospitals.name as hospital_name', 'specialties.name as specialty_name');
                }
                $amount_paid_details = $amount_paid_details->join("physicians", "physicians.id", "=", "amount_paid.physician_id")
                    ->join("contracts", "contracts.id", "=", "amount_paid.contract_id")
                    ->join("practices", "practices.id", "=", "amount_paid.practice_id")
                    ->join("payment_types", "payment_types.id", "=", "contracts.payment_type_id")
                    ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
                    ->join("specialties", "specialties.id", "=", "physicians.specialty_id");

                if ($region_hospital) {
                    $amount_paid_details = $amount_paid_details->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id")
                        ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");
                }
                $amount_paid_details = $amount_paid_details->whereIn("amount_paid.contract_id", $all_contract)
                    ->where("physicians.id", "=", $physician_id)
                    ->where("hospitals.id", "=", $hospital_id)
                    ->where("amount_paid.amountPaid", "!=", 0)
                    //   ->where("physician_logs.approval_date","!=","0000-00-00")
                    ->where("amount_paid.start_date", ">=", $month_start_date)
                    ->where("amount_paid.end_date", "<=", $month_end_date)
                    ->where("contracts.physician_id", "=", $physician_id)
                    ->orderBy('contract_id')
                    ->groupBy('contracts.physician_id')
                    ->groupBy('contracts.practice_id')
                    ->get();

                $sum_worked_hours = PhysicianLog::select(DB::raw("sum(physician_logs.duration) as sum_worked_hours"))
                    ->where("physician_logs.physician_id", "=", $physician_id)
                    ->whereIn("physician_logs.contract_id", $all_contract)
                    ->where("physician_logs.approval_date", "!=", "0000-00-00")
                    ->whereNull('physician_logs.deleted_at')
                    ->get();

                if (count($amount_paid_details) > 0) {
                    foreach ($amount_paid_details as $amount_paid_detail) {
                        if (($amount_paid_detail->amountPaidTotal) != null) {
                            $amount_paid_detail->sum_worked_hours = $sum_worked_hours;
                            $key = $amount_paid_detail->contract_id . "_" . $amount_paid_detail->start_date . "_" . $amount_paid_detail->end_date;
                            $contract_data[$key][] = $amount_paid_detail;
                        }
                    }
                }
            }

            if (count($contract_data) > 0) {
                $result[$j] = [
                    "Period" => format_date($months_start) . " - " . format_date($months_end),
                    "agreement_name" => $agreement->name,
                    "agreement_start_date" => $agreement->start_date,
                    "agreement_end_date" => $agreement->end_date,
                    "localtimeZone" => $localtimeZone,
                    "payment_detail" => $contract_data,
                ];
                $j++;
            }
            $i++;
        }
        return $result;
    }
}
