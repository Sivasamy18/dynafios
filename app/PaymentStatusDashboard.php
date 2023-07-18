<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Log;
use App\customClasses\PaymentFrequencyFactoryClass;
use App\customClasses\PaymentTypeFactoryClass;
use Illuminate\Support\Facades\DB;
use Request;
use Auth;
use DateTime;
use DateTimeZone;
use Artisan;
use App\Console\Commands\ApprovalStatusReport;
use Lang;
use Redirect;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\Jobs\UpdatePaymentStatusDashboard;
use Illuminate\Database\Eloquent\SoftDeletes;
use function App\Start\payment_status_dashboard_report_path;

class PaymentStatusDashboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payment_status_dashboard';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function updatePaymentStatusDashboard($physician_id, $practice_id, $contract_id, $contract_name_id, $hospital_id, $agreement_id, $selected_date)
    {

        $agreement_details = Agreement::findOrFail($agreement_id);
        $contract_obj = Contract::withTrashed()->findOrFail($contract_id);

        // Get the frequency type for the agreement and then get the periods for that frequency type.
        $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
        $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
        $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement_details);
        $period_range = $res_pay_frequency['date_range_with_start_end_date'];

        $period_min = '';
        $period_max = '';
        foreach ($period_range as $period) {
            if (strtotime($selected_date) >= strtotime($period['start_date']) && strtotime($selected_date) <= strtotime($period['end_date'])) {
                $period_min = $period['start_date'];
                $period_max = $period['end_date'];
            }
        }

        $contracts_arr[] = $contract_id;
        $agreement_ids[] = $agreement_id;

        $ratesArray = ContractRate::contractRates($contracts_arr);
        if (!empty($period_min) && !empty($period_max)) {
            if ($contract_obj->deleted_at) {
                $exist_payment_status_deleted_contract = self::where('physician_id', '=', $physician_id)
                    ->where('contract_id', '=', $contract_id)
                    ->where('hospital_id', '=', $hospital_id)
                    ->where('practice_id', '=', $practice_id)
                    ->where('period_min_date', '=', mysql_date($period_min))
                    ->where('period_max_date', '=', mysql_date($period_max))
                    ->whereNull('deleted_at')
                    ->first();

                if ($exist_payment_status_deleted_contract) {
                    $delete_payment_status = self::where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('hospital_id', '=', $hospital_id)
                        ->where('practice_id', '=', $practice_id)
                        ->where('period_min_date', '=', mysql_date($period_min))
                        ->where('period_max_date', '=', mysql_date($period_max))
                        ->delete();

                    if ($delete_payment_status) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            }

            $logs_obj = LogApproval::LogsForApproverPaymentStatus(0, 0, $contract_obj->payment_type_id, $contract_obj->contract_type_id, $hospital_id, $agreement_id, $practice_id, $physician_id, $period_min, $period_max, $agreement_ids, $contracts_arr, $report = true, $contract_id, $contract_name_id);

            $payment_type_factory = new PaymentTypeFactoryClass();
            $result = null;
            if (count($logs_obj['sorted_logs_data']) > 0) {
                foreach ($logs_obj['sorted_logs_data'] as $payment_type_id => $logs) {
                    $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($payment_type_id); // This line is returns the object for calculation based on payment type.
                    $result = $payment_type_obj->calculatePayment($logs, $ratesArray);
                }
                if ($contract_obj->payment_type_id != PaymentType::PER_DIEM && $contract_obj->payment_type_id != PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    if ($result) {
                        $calculated_expected_hours = number_format(count($result['unique_date_range_arr']) * $contract_obj->expected_hours, 2);
                    } else {
                        $calculated_expected_hours = 0;
                    }

                    if ($contract_obj->payment_type_id == PaymentType::STIPEND) {
                        $calculated_payment = $result['calculated_payment'];
                    }
                } else {
                    $calculated_expected_hours = 0;
                }

                $log_ids_arr = $logs_obj['logs']->pluck('log_id')->toArray();

                $pending_logs_flag = '0';
                $approved_logs_flag = '0';
                $rejected_logs_flag = '0';

                $pending_logs_hours = 0.00;
                $approved_logs_hours = 0.00;
                $rejected_logs_hours = 0.00;

                if (count($log_ids_arr) > 0) {
                    /* $check_status_pending = PhysicianLog::where("physician_logs.signature", "=", 0)
                        ->where("physician_logs.approval_date", "=", "0000-00-00")
                        ->whereIn('physician_logs.id', $log_ids_arr)
                        ->get(); */

                    $check_status_pending = PhysicianLog::where("physician_logs.signature", "=", 0)
                        ->where("physician_logs.approval_date", "=", "0000-00-00")
                        ->where("physician_logs.next_approver_user", "=", "0")
                        ->whereIn('physician_logs.id', $log_ids_arr)
                        ->get();

                    if (count($check_status_pending) > 0) {
                        $pending_logs_flag = '1';
                        $pending_logs_hours = $check_status_pending->sum('duration');
                    }

                    $check_status_rejected = PhysicianLog::join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                        // ->join('log_approval_history', 'log_approval_history.log_id', '=', 'physician_logs.id')
                        ->where("physician_logs.signature", "=", 0)
                        ->where("physician_logs.approval_date", "=", "0000-00-00")
                        ->where("log_approval.role", "=", 1)
                        ->where("log_approval.approval_status", "=", 0)
                        ->whereIn('physician_logs.id', $log_ids_arr)
                        ->distinct()
                        ->get();

                    if (count($check_status_rejected) > 0) {
                        $rejected_logs_flag = '1';
                        $rejected_logs_hours = $check_status_rejected->sum('duration');

                        if ($pending_logs_hours > 0 && $pending_logs_hours >= $rejected_logs_hours) {
                            $pending_logs_hours = $pending_logs_hours - $rejected_logs_hours;
                        }

                    }
                }

                // Below condition written separately because LogApproval::LogsForApprover() function only returns the logs which are in approval process.
                $check_status_approved = PhysicianLog::where("physician_logs.signature", "!=", 0)
                    ->where("physician_logs.approval_date", "!=", "0000-00-00")
                    ->where('physician_logs.physician_id', '=', $physician_id)
                    ->where('physician_logs.practice_id', '=', $practice_id)
                    ->where('physician_logs.contract_id', '=', $contract_id)
                    ->whereBetween('physician_logs.date', [mysql_date($period_min), mysql_date($period_max)])
                    ->get();

                if (count($check_status_approved) > 0) {
                    $approved_logs_flag = '1';
                    $approved_logs_hours = $check_status_approved->sum('duration');
                }
                // Get all fully approved logs for periods for calculating the amount to be paid and check if the fully paid or not.
                $logs_for_calculation = PhysicianLog::select(
                    DB::raw("physician_logs.date as log_date"),
                    DB::raw("physician_logs.duration as duration"),
                    DB::raw("physician_logs.id as log_id"),
                    DB::raw("physician_logs.action_id as action_id"), //get action id to get change name
                    DB::raw("actions.name as action"),
                    DB::raw("physician_logs.practice_id as practice_id"),
                    DB::raw("physician_logs.contract_id as contract_id"),
                    DB::raw("physician_logs.physician_id as physician_id"),
                    DB::raw("physician_logs.log_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.payment_type_id as payment_type_id"), //get payment type id for logs
                    DB::raw("contracts.contract_type_id as contract_type_id"), //get contract type id to get change name for on call type of contract
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.agreement_id as agreement_id"),
                    DB::raw("IF(physician_logs.physician_id = " . $physician_id . ", 'Waiting', 'NA') as current_user_status")
                )
                    ->distinct('physician_logs.id')
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
                    ->where("physician_logs.signature", "!=", 0)
                    ->whereNull("physician_logs.deleted_at")
                    ->where("physician_logs.approval_date", "!=", "0000-00-00")
                    ->where("physician_logs.practice_id", '=', $practice_id)
//                                                    ->where("physician_logs.physician_id", '=', $physician_id)
                    ->where("physician_logs.contract_id", '=', $contract_id)
                    ->whereBetween('physician_logs.date', [mysql_date($period_min), mysql_date($period_max)])
                    ->get();

                if (count($logs_for_calculation) > 0) {
                    $calculated_payment = null;
                    $payment_type_factory = new PaymentTypeFactoryClass();
                    $payment_type_obj = $payment_type_factory->getPaymentCalculationClass($contract_obj->payment_type_id); // This line is returns the object for calculation based on payment type.
                    $calculation_result = $payment_type_obj->calculatePayment($logs_for_calculation, $ratesArray);
                    if ($calculation_result) {
                        $calculated_payment = $calculation_result['calculated_payment'];
                    }

                    $get_amount_paid = Amount_paid::select('amount_paid.*')
                        ->join('amount_paid_physicians', 'amount_paid_physicians.amt_paid_id', '=', 'amount_paid.id')
                        ->where('amount_paid.start_date', '=', mysql_date($period_min))
                        ->where('amount_paid.end_date', '=', mysql_date($period_max))
                        ->where('amount_paid_physicians.physician_id', '=', $physician_id)
                        ->where('amount_paid.contract_id', '=', $contract_id)
                        ->where('amount_paid.practice_id', '=', $practice_id)
                        ->get();

                    if (count($get_amount_paid) > 0) {
                        $amount_paid = $get_amount_paid->sum('amountPaid');
                        /**
                         * If the amount paid and calculated payment is same then set approval flag to flase else keep it as true to show on payment status dashboard.
                         **/
                        if ($amount_paid == $calculated_payment) {
                            if (count($check_status_approved) > 0) {
                                $approved_logs_flag = '0';
                                $approved_logs_hours = 0.00;
                            }
                        }
                    }
                } else {
                    // log::info('No fully approved logs found for payment status dashboard update.');
                }

                $hours_to_approve = array_sum($logs_obj['logs']->pluck('duration')->toArray());
                // $hours_to_approve = array_sum($logs_obj['logs']->where('physician_id', '=', $physician_id)->pluck('duration')->toArray());

                $hours_to_approve = number_format($hours_to_approve, 2);
                $expected_hours = number_format(str_replace(",", "", $calculated_expected_hours), 2);

                $exist_payment_status_dashboard = self::where('physician_id', '=', $physician_id)
                    ->where('contract_id', '=', $contract_id)
                    ->where('hospital_id', '=', $hospital_id)
                    ->where('practice_id', '=', $practice_id)
                    ->where('period_min_date', '=', mysql_date($period_min))
                    ->where('period_max_date', '=', mysql_date($period_max))
                    ->whereNull('deleted_at')
                    ->first();

                // if($calculated_payment <= 0.00 && $exist_payment_status_dashboard){
                //     self::where('physician_id', '=',$physician_id)
                //         ->where('contract_id', '=',$contract_id)
                //         ->where('hospital_id', '=',$hospital_id)
                //         ->where('period_min_date', '=',mysql_date($period_min))
                //         ->where('period_max_date', '=',mysql_date($period_max))
                //         ->delete();
                //     return 1;
                // }

                if ($exist_payment_status_dashboard) {
                    $exist_payment_status_dashboard->hours_approving = $hours_to_approve;
                    $exist_payment_status_dashboard->expected_hours = $expected_hours;
                    $exist_payment_status_dashboard->pending_logs = $pending_logs_flag;
                    $exist_payment_status_dashboard->approved_logs = $approved_logs_flag;
                    $exist_payment_status_dashboard->rejected_logs = $rejected_logs_flag;

                    $exist_payment_status_dashboard->approved_logs_hours_approving = $approved_logs_hours;
                    $exist_payment_status_dashboard->pending_logs_hours_approving = $pending_logs_hours;
                    $exist_payment_status_dashboard->rejected_logs_hours_approving = $rejected_logs_hours;

                    if ($exist_payment_status_dashboard->save()) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    $payment_status_dashboard = new self();
                    $payment_status_dashboard->physician_id = $physician_id;
                    $payment_status_dashboard->practice_id = $practice_id;
                    $payment_status_dashboard->contract_id = $contract_id;
                    $payment_status_dashboard->contract_name_id = $contract_name_id;
                    $payment_status_dashboard->hospital_id = $hospital_id;
                    $payment_status_dashboard->period_min_date = $period_min;
                    $payment_status_dashboard->period_max_date = $period_max;
                    // $payment_status_dashboard->is_period_paid = 0;
                    $payment_status_dashboard->hours_approving = $hours_to_approve;
                    $payment_status_dashboard->expected_hours = $expected_hours;
                    $payment_status_dashboard->pending_logs = $pending_logs_flag;
                    $payment_status_dashboard->approved_logs = $approved_logs_flag;
                    $payment_status_dashboard->rejected_logs = $rejected_logs_flag;

                    $payment_status_dashboard->approved_logs_hours_approving = $approved_logs_hours;
                    $payment_status_dashboard->pending_logs_hours_approving = $pending_logs_hours;
                    $payment_status_dashboard->rejected_logs_hours_approving = $rejected_logs_hours;

                    if ($payment_status_dashboard->save()) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            } else {

                // Below condition written separately because LogApproval::LogsForApprover() function only returns the logs which are in approval process. Here we will check for fully approved logs for that period.
                $check_approved_logs_exist = PhysicianLog::where("physician_logs.signature", "!=", 0)
                    ->where("physician_logs.approval_date", "!=", "0000-00-00")
                    ->where('physician_logs.physician_id', '=', $physician_id)
                    ->where('physician_logs.practice_id', '=', $practice_id)
                    ->where('physician_logs.contract_id', '=', $contract_id)
                    ->whereNull('physician_logs.deleted_at')
                    ->whereBetween('physician_logs.date', [mysql_date($period_min), mysql_date($period_max)])
                    ->get();

                // Below condition is checking if approved logs exists if available we will keep the entry in payment status dashboard and, if not then all logs are deleted hence we can delete the entry from payment status dashboard.
                if (count($check_approved_logs_exist) == 0) {
                    $exist_payment_status_dashboard = self::where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('hospital_id', '=', $hospital_id)
                        ->where('practice_id', '=', $practice_id)
                        ->where('period_min_date', '=', mysql_date($period_min))
                        ->where('period_max_date', '=', mysql_date($period_max))
                        ->whereNull('deleted_at')
                        ->first();
                    if ($exist_payment_status_dashboard) {
                        $delete_payment_status = self::where('physician_id', '=', $physician_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('hospital_id', '=', $hospital_id)
                            ->where('practice_id', '=', $practice_id)
                            ->where('period_min_date', '=', mysql_date($period_min))
                            ->where('period_max_date', '=', mysql_date($period_max))
                            ->delete();

                        if ($delete_payment_status) {
                            return 1;
                        } else {
                            return 0;
                        }
                    } else {
                        // log::info('No data found for payment status update. for contract - ' . $contract_id);
                    }
                } else {
                    return 1;
                }
            }
        }
    }

    public static function PaymentStatusDashboardReport($user_id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, $report_type, $status, $show_calculated_payment, $group_id, $timestamp, $timeZone, $approver)
    {
        $LogApproval = new LogApproval();

        if ($report_type == 1) {
            $dataWithDate = $LogApproval->PaymentStatusDashboardReport($user_id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, $status, true, 0, $group_id, $approver);
            $data = $dataWithDate['items'];
        }

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

        Artisan::call('reports:approvalstatus', [
            "report_data" => $data,
            "report_type" => $report_type,
            "localtimeZone" => $localtimeZone,
            "show_calculated_payment" => $show_calculated_payment,
            "user_id" => $user_id
        ]);

        if (!ApprovalStatusReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(ApprovalStatusReport::$message)
            ]);
        } else {
            // Send report to user on email id
            $sucess_report = ApprovalStatusReport::$success;
            $report_file_name = ApprovalStatusReport::$report_filename;

            if ($sucess_report && $report_file_name != "") {
                $report_path = payment_status_dashboard_report_path(null, $user_id); // payment_status_report_path();
                $user = User::FindOrFail($user_id);

                $data['name'] = $user->first_name . " " . $user->last_name;
                $data['email'] = $user->email;
                $data['file1'] = $report_path . "/" . $report_file_name;
                $data["type"] = EmailSetup::PAYMENT_STATUS_DASHBOARD_REPORT;
                $data['with'] = [
                    'name' => $user->first_name . " " . $user->last_name,
                ];
                $data['attachment'] = [
                    'file1' => [
                        'file' => $report_path . "/" . $report_file_name,
                        'file_name' => $report_file_name
                    ]
                ];
                try {
                    EmailQueueService::sendEmail($data);
                } catch (Exception $e) {
                    Log::debug('Payment Status Dashboard Report Send Mail error ' . $e->getMessage());
                }
            }
        }
    }

    public static function updatePaymentStatus($hospital_id, $agreement_id, $contract_id)
    {
        ini_set('max_execution_time', 12000);

        if ($hospital_id == 0) {
            $hospitals = Hospital::where('archived', '=', 0)->whereNull('deleted_at')->distinct()->get();
        } else {
            $hospitals = Hospital::where('id', '=', $hospital_id)->get();
        }

        if ($hospitals) {
            foreach ($hospitals as $hospital) {
                if ($agreement_id > 0) {
                    $agreements = Agreement::where('hospital_id', '=', $hospital->id)->where('id', '=', $agreement_id)->get();
                } else {
                    $agreements = Agreement::where('hospital_id', '=', $hospital->id)->get();
                }

                if ($agreements) {
                    foreach ($agreements as $agreement) {
                        if ($contract_id > 0) {
                            $contracts = Contract::where('agreement_id', $agreement->id)->withTrashed()->where('id', '=', $contract_id)->get();
                        } else {
                            $contracts = Contract::where('agreement_id', $agreement->id)->withTrashed()->get();
                        }

                        if ($contracts) {
                            foreach ($contracts as $contract) {
                                // Get the frequency type for the agreement and then get the periods for that frequency type.
                                $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
                                $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
                                $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement);
                                $period_range = $res_pay_frequency['date_range_with_start_end_date'];

                                /* foreach($period_range as $period){
                                    $period_min = $period['start_date'];
                                    $period_max = $period['end_date'];
                                    UpdatePaymentStatusDashboard::dispatch($contract->physician_id, $contract->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $period_min);
                                } */

                                foreach ($period_range as $period) {
                                    $period_min = $period['start_date'];
                                    $period_max = $period['end_date'];

                                    $physician_contracts = PhysicianContracts::select('physician_id', 'practice_id')
                                        ->where('contract_id', '=', $contract->id)->get();

                                    foreach ($physician_contracts as $physician_contract) {
                                        UpdatePaymentStatusDashboard::dispatch($physician_contract->physician_id, $physician_contract->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $period_min);
                                    }
                                }
                            }
                        } else {
                            // log::info('Contracts not found');
                        }
                    }
                } else {
                    // log::info('Agreement not found');    
                }
            }
        } else {
            // log::info('Hospital not found');    
        }
        // log::info('Success');
        return 1;
    }
}
