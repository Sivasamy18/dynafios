<?php

namespace App\Http\Controllers;

use App\Agreement;
use App\ContractType;
use App\Group;
use App\Hospital;
use App\User;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;


class AgreementExpiryReminderController extends ResourceController
{
    public function renewalReminderMail()
    {
        $day_after_30_days = date('Y-m-d', strtotime("+30 days"));
        $today = date('Y-m-d');
        $agreement_expiring_hospitals = Agreement::where('agreements.end_date', '=', $day_after_30_days)
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.hospital_id')
            ->get();

        foreach ($agreement_expiring_hospitals as $index => $expiring_agreement_hospital) {
            $data = [];
            $contract_types = ContractType::select('contract_types.*')
                ->orderBy('contract_types.id')
                ->get();
            $agreement_by_type = [];
            $hospital = Hospital::findOrFail($expiring_agreement_hospital->hospital_id);
            $sent = 0;

            foreach ($contract_types as $index1 => $contract_type) {
                $agreements = Agreement::select('agreements.*')
                    ->where('agreements.end_date', '=', $day_after_30_days)
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where('contracts.contract_type_id', '=', $contract_type->id)
                    ->where('agreements.hospital_id', '=', $expiring_agreement_hospital->hospital_id)
                    ->groupBy('agreements.id')
                    ->get();
                if (count($agreements) > 0) {
                    $sent = 1;
                }
                $agreement_by_type[$contract_type->id] = array("agreement" => $agreements, "type" => $contract_type->name);
            }

            $hospital_user = User::select('users.*')
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                ->where('hospital_user.hospital_id', '=', $expiring_agreement_hospital->hospital_id)
                ->where('users.group_id', '=', Group::HOSPITAL_ADMIN)
                ->get();

            $hospital_superuser = User::select('users.*')
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                ->where('hospital_user.hospital_id', '=', $expiring_agreement_hospital->hospital_id)
                ->where('users.group_id', '=', Group::SUPER_HOSPITAL_USER)
                ->get();

            $data['hospital'] = $hospital->name;
            $data['hospital_users'] = $hospital_user;
            $data['hospital_superusers'] = $hospital_superuser;
            $data['agreement_by_type'] = $agreement_by_type;
            $hospital_users = json_decode($hospital_user);
            $hospital_superuser = json_decode($hospital_superuser);

            if ($sent > 0) {
                /*for ($i = 0; $i < count($hospital_users); $i++) {
                    $data['name'] = ucfirst($hospital_users[$i]->first_name) . ' ' . ucfirst($hospital_users[$i]->last_name);
                    $data['email'] = $hospital_users[$i]->email;

                    Mail::send('emails/hospitals/agreementExpiryReminder', $data, function ($message) use ($data) {
                        $message->to($data['email'], $data['name']);
                        $message->subject('DYNAFIOS Agreement Expiration Notice');
                    });
                }*/
                for ($i = 0; $i < count($hospital_superuser); $i++) {
                    $data['name'] = ucfirst($hospital_superuser[$i]->first_name) . ' ' . ucfirst($hospital_superuser[$i]->last_name);
                    $data['email'] = $hospital_superuser[$i]->email;
                    $data['type'] = EmailSetup::AGREEMENT_EXPIRY_REMINDER;
                    $data['with'] = [
                        'name' => ucfirst($hospital_superuser[$i]->first_name) . ' ' . ucfirst($hospital_superuser[$i]->last_name),
                        'agreement_by_type' => $agreement_by_type,
                        'hospital' => $hospital->name
                    ];

                    EmailQueueService::sendEmail($data);
                }
            }
        }
        //return View::make('emails/hospitals/agreementExpiryReminder')->with($data);
    }

    public function reportReminderMail()
    {
        $agreement_hospitals = Agreement::select('agreements.hospital_id')->whereRaw('agreements.end_date >= NOW()')
            ->join('hospitals', 'agreements.hospital_id', '=', 'hospitals.id')
            ->whereRaw('agreements.archived = 0')
            ->whereNull('hospitals.deleted_at')
            ->where('hospitals.archived', '=', false)
            ->where('agreements.send_invoice_day', '=', date("d"))
//            ->groupBy('agreements.hospital_id')
            ->distinct()
            ->get();

        foreach ($agreement_hospitals as $agreement_hospital) {
            $data = [];
            $users = User::select('users.*')
                ->join('agreements', function ($join) {
                    $join->on('users.id', '=', 'agreements.invoice_reminder_recipient_1')
                        ->orOn('users.id', '=', 'agreements.invoice_reminder_recipient_2');
                })
                ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                ->join('hospitals', 'agreements.hospital_id', '=', 'hospitals.id')
                ->whereRaw('agreements.end_date >= NOW()')
                ->whereRaw('agreements.archived = 0')
                ->whereRaw('agreements.is_deleted = 0')
                ->whereNull('hospitals.deleted_at')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.send_invoice_day', '=', date("d"))
                ->where('agreements.hospital_id', '=', $agreement_hospital->hospital_id)
                ->distinct()
                ->get();
            foreach ($users as $user) {
                $contract_types = ContractType::select('contract_types.*')
                    ->orderBy('contract_types.id')
                    ->get();
                $agreement_by_type = [];
                $hospital = Hospital::findOrFail($agreement_hospital->hospital_id);
                $sent = 0;

                foreach ($contract_types as $index1 => $contract_type) {
                    $agreements = Agreement::select('agreements.*')
                        ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                        ->join('hospitals', 'agreements.hospital_id', '=', 'hospitals.id')
                        ->whereRaw('agreements.end_date >= NOW()')
                        ->whereRaw('agreements.archived = 0')
                        ->whereRaw('agreements.is_deleted = 0')
                        ->whereNull('hospitals.deleted_at')
                        ->where('hospitals.archived', '=', false)
                        ->where('agreements.send_invoice_day', '=', date("d"))
                        ->where('contracts.contract_type_id', '=', $contract_type->id)
                        ->where('agreements.hospital_id', '=', $agreement_hospital->hospital_id)
                        ->where(function ($pass) use ($user) {
                            $pass->where('agreements.invoice_reminder_recipient_1', '=', $user->id)
                                ->where('agreements.invoice_reminder_recipient_1_opt_in_email', '=', '1')
                                ->orWhere(function ($pass1) use ($user) {
                                    $pass1->where('agreements.invoice_reminder_recipient_2', '=', $user->id)
                                        ->where('agreements.invoice_reminder_recipient_2_opt_in_email', '=', '1');
                                });
                        })
                        ->groupBy('agreements.id')
                        ->get();
                    if (count($agreements) > 0) {
                        $sent = 1;
                    }
                    $agreement_by_type[$contract_type->id] = array("agreement" => $agreements, "type" => $contract_type->name);

                }

//            $hospital_user = User::select('users.*')
//                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
//                ->where('hospital_user.hospital_id', '=', $hospital_id)
//                ->where('users.group_id', '=', Group::HOSPITAL_ADMIN)
//                ->get();
//
//            $hospital_superuser = User::select('users.*')
//                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
//                ->where('hospital_user.hospital_id', '=', $hospital_id)
//                ->where('users.group_id', '=', Group::SUPER_HOSPITAL_USER)
//                ->get();

                $data['hospital'] = $hospital->name;
//            $data['hospital_users'] = $hospital_user;
//            $data['hospital_superusers'] = $hospital_superuser;
                $data['agreement_by_type'] = $agreement_by_type;
//            $hospital_users = json_decode($hospital_user);
//            $hospital_superuser = json_decode($hospital_superuser);

                if ($sent > 0) {
                    $data['name'] = ucfirst($user->first_name) . ' ' . ucfirst($user->last_name);
                    $data['email'] = $user->email;
                    $data['type'] = EmailSetup::REPORT_REMINDER_MAIL;
                    $data['with'] = [
                        'name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name)
                    ];

                    EmailQueueService::sendEmail($data);

//                for ($i = 0; $i < count($hospital_users); $i++) {
//                    $data['name'] = ucfirst($hospital_users[$i]->first_name) . ' ' . ucfirst($hospital_users[$i]->last_name);
//                    $data['email'] = $hospital_users[$i]->email;
//
//                    Mail::send('emails/hospitals/reportReminderMail', $data, function ($message) use ($data) {
//                        $message->to($data['email'], $data['name']);
//                        $message->subject('DYNAFIOS Reporting and Payment Reminder');
//                    });
//                }

//                for ($i = 0; $i < count($hospital_superuser); $i++) {
//                    $data['name'] = ucfirst($hospital_superuser[$i]->first_name) . ' ' . ucfirst($hospital_superuser[$i]->last_name);
//                    $data['email'] = $hospital_superuser[$i]->email;
//
//                    Mail::send('emails/hospitals/reportReminderMail', $data, function ($message) use ($data) {
//                        $message->to($data['email'], $data['name']);
//                        $message->subject('DYNAFIOS Reporting and Payment Reminder');
//                    });
//                }
                }
            }
        }
        //return View::make('emails/hospitals/reportReminderMail')->with($data);
    }
}

?>