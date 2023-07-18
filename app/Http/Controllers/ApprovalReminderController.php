<?php

namespace App\Http\Controllers;

use App\Console\Commands\BreakdownReportCommand;
use App\Console\Commands\BreakdownReportCommandMultipleMonths;
use App\Console\Commands\HospitalInvoiceCommand;
use App\LogApproval;
use App\User;
use App\Agreement;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Swift_TransportException;
use function App\Start\hospital_report_path;

class ApprovalReminderController extends ResourceController
{
    public function approveLogCM()
    {
        $agreements = new Agreement();
        $active_users = $agreements->getAllActiveAgreementApprovalData(LogApproval::contract_manager);
        foreach ($active_users as $active_user_id => $contracts) {
            $contract_list = '';
            $contractNameID = 0;
            $c = 1;
            if (count($contracts) > 0) {
                $user = User::where("id", "=", $active_user_id)->first();
                if ($user) {
                    foreach ($contracts as $contract) {
                        if ($contract[0]['contract_name_id'] != $contractNameID) {
                            if (count($contract[0]) == $c) {
                                $contract_list = $contract_list . ',' . $contract[0]['contract_name'];
                            } else {
                                $contract_list = $contract_list . $contract[0]['contract_name'] . ',';
                                $c++;
                            }

                        }
                    }
                    $data["email"] = $user->email;
                    $data["name"] = $user->first_name;
                    $data["contract_list"] = $contract_list;
                    $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_CM;
                    $data['with'] = [
                        'name' => $user->first_name,
                        'contract_list' => $contract_list
                    ];
                    $data['subject_param'] = [
                        'name' => '',
                        'date' => date("F", strtotime("-1 months")) . ' ' . date("Y", strtotime("-1 months")),
                        'month' => '',
                        'year' => '',
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];
//                        $data["hospital_id"] = $agreement['hospital_id'];
                    try {
                        EmailQueueService::sendEmail($data);

                        sleep(5);
                    } catch (Swift_TransportException $e) {
                        Mail::getSwiftMailer()->getTransport()->stop();
                        sleep(20); // Just in case ;-)
                    }
                }
            } else {
            }
        }
    }

    public function approveLogFM()
    {
        $agreements = new Agreement();
        $active_users = $agreements->getAllActiveAgreementApprovalData(LogApproval::financial_manager);
        foreach ($active_users as $active_user_id => $contracts) {
            $contract_list = '';
            $contractNameID = 0;
            $c = 1;
            if (count($contracts) > 0) {
                $user = User::where("id", "=", $active_user_id)->first();
                if ($user) {
                    foreach ($contracts as $contract) {
                        if ($contract[0]['contract_name_id'] != $contractNameID) {
                            if (count($contract[0]) == $c) {
                                $contract_list = $contract_list . ',' . $contract[0]['contract_name'];
                            } else {
                                $contract_list = $contract_list . $contract[0]['contract_name'] . ',';
                                $c++;
                            }

                        }
                    }
                    $data["email"] = $user->email;
                    $data["name"] = $user->first_name;
                    $data["contract_list"] = $contract_list;
                    $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_FM;
                    $data['with'] = [
                        'name' => $user->first_name,
                        'contract_list' => $contract_list
                    ];
                    $data['subject_param'] = [
                        'name' => '',
                        'date' => date("F", strtotime("-1 months")) . ' ' . date("Y", strtotime("-1 months")),
                        'month' => '',
                        'year' => '',
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];
//                        $data["hospital_id"] = $agreement['hospital_id'];
                    try {
                        EmailQueueService::sendEmail($data);

                        sleep(5);
                    } catch (Swift_TransportException $e) {
                        Mail::getSwiftMailer()->getTransport()->stop();
                        sleep(20); // Just in case ;-)
                    }
                }
            } else {
            }
        }
    }

    public function approveLogEM()
    {
        $agreements = new Agreement();
        $active_users = $agreements->getAllActiveAgreementApprovalData(LogApproval::executive_manager);
        foreach ($active_users as $active_user_id => $contracts) {
            $contract_list = '';
            $contractNameID = 0;
            $c = 1;
            if (count($contracts) > 0) {
                $user = User::where("id", "=", $active_user_id)->first();
                if ($user) {
                    foreach ($contracts as $contract) {
                        if ($contract[0]['contract_name_id'] != $contractNameID) {
                            if (count($contract[0]) == $c) {
                                $contract_list = $contract_list . ',' . $contract[0]['contract_name'];
                            } else {
                                $contract_list = $contract_list . $contract[0]['contract_name'] . ',';
                                $c++;
                            }

                        }
                    }
                    $data["email"] = $user->email;
                    $data["name"] = $user->first_name;
                    $data["contract_list"] = $contract_list;
                    $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_EM;
                    $data['with'] = [
                        'name' => $user->first_name,
                        'contract_list' => $contract_list
                    ];
                    $data['subject_param'] = [
                        'name' => '',
                        'date' => date("F", strtotime("-1 months")) . ' ' . date("Y", strtotime("-1 months")),
                        'month' => '',
                        'year' => '',
                        'requested_by' => '',
                        'manager' => '',
                        'subjects' => ''
                    ];
//                        $data["hospital_id"] = $agreement['hospital_id'];
                    try {
                        EmailQueueService::sendEmail($data);

                        sleep(5);
                    } catch (Swift_TransportException $e) {
                        Mail::getSwiftMailer()->getTransport()->stop();
                        sleep(20); // Just in case ;-)
                    }
                }
            } else {
            }
        }
    }

    public function invoiceWithLogReports()
    {
        $agreements = new Agreement();
        $active_agreements = $agreements->getAllApproveLogsAgreement();
        foreach ($active_agreements as $agreement) {
            if (isset($agreement["paid_data"]) && count($agreement["paid_data"]) > 0 && isset($agreement["approved_logs"]) && count($agreement["approved_logs"]) > 0) {

                Artisan::call('invoices:hospital', [
                    'hospital' => $agreement["hospital"]->id,
                    'contract_type' => $agreement["contract_type"],
                    'practices' => $agreement["practices"],
                    'agreements' => $agreement["agreements"],
                    'months' => $agreement["months"],
                    'data' => $agreement["paid_data"]
                ]);

                if (!HospitalInvoiceCommand::$success) {
//                return Redirect::back()->with([
//                    'error' => Lang::get(HospitalInvoiceCommand::$message)
//                ]);
                }

                $report_id = HospitalInvoiceCommand::$report_id;
                $report_path = hospital_report_path($agreement["hospital"]);
                $report_filename = HospitalInvoiceCommand::$report_filename;

                Artisan::call('reports:breakdownmultiplemonths', [
                    'hospital' => $agreement["hospital"]->id,
                    'contract_type' => $agreement["contract_type"],
                    'physicians' => $agreement["physicians"],
                    'agreements' => $agreement["agreements"],
                    'months' => $agreement["months"],
                    "report_data" => $agreement["approved_logs"]
                ]);

                if (!BreakdownReportCommandMultipleMonths::$success) {
//                    return Redirect::back()->with([
//                        'error' => Lang::get(BreakdownReportCommand::$message)
//                    ]);
                }

                $log_filename = BreakdownReportCommand::$report_filename;
                $data['month'] = date("F", strtotime($agreement['start_date']));
                $data['year'] = date("Y", strtotime($agreement['start_date']));
                $data['name'] = "DYNAFIOS";
                $data['email'] = $agreement["recipient"];
                $data['file1'] = $report_path . "/" . $report_filename;
                $data['file2'] = $report_path . "/" . $log_filename;
                $data["type"] = EmailSetup::EMAIL_INVOICE_WITH_LOG;
                $data['with'] = [
                    'name' => "DYNAFIOS",
                    'month' => date("F", strtotime($agreement['start_date'])),
                    'year' => date("Y", strtotime($agreement['start_date']))
                ];
                $data['subject_param'] = [
                    'name' => '',
                    'date' => '',
                    'month' => date("F", strtotime($agreement['start_date'])),
                    'year' => date("Y", strtotime($agreement['start_date'])),
                    'requested_by' => '',
                    'manager' => '',
                    'subjects' => ''
                ];
                $data['attachment'] = [
                    'file1' => [
                        'file' => $report_path . "/" . $report_filename,
                        'file_name' => 'invoice.xlsx'
                    ],
                    'file2' => [
                        'file' => $report_path . "/" . $log_filename,
                        'file_name' => "physician_log.xlsx"
                    ]
                ];
                try {
                    EmailQueueService::sendEmail($data);
                    sleep(5);
                } catch (Swift_TransportException $e) {
                    Mail::getSwiftMailer()->getTransport()->stop();
                    sleep(20); // Just in case ;-)
                }
            }
        }
    }
}

?>