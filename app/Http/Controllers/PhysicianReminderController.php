<?php

namespace App\Http\Controllers;

use Edujugon\PushNotification\PushNotification;
use App\Physician;
use App\Contract;
use App\PhysicianDeviceToken;
use App\Services\NotificationService;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Exception;
use Illuminate\Support\Facades\Log;
use App\PhysicianLog;
use Illuminate\Support\Facades\Mail;
use Swift_TransportException;

class PhysicianReminderController extends ResourceController
{
    public function enterLog()
    {
        $previous_month_first_day = date('Y-m-d', strtotime("first day of -1 month"));
        $previous_month_last_day = date('Y-m-d', strtotime("last day of -1 month"));

        $physicians = Physician::all();
        foreach ($physicians as $physician) {
            $contracts = new Contract();
            $active_contracts = $contracts->getActiveContract($physician);
            $logs = PhysicianLog::select('physician_logs.*')
                ->where('physician_logs.physician_id', '=', $physician->id)
                ->whereBetween('physician_logs.date', [$previous_month_first_day, $previous_month_last_day])
                ->count();

            if (count($active_contracts) > 0 && $logs == 0) {
                $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->get();
                $data['name'] = $physician->first_name;
                $data['last_name'] = $physician->last_name;
                $data['physician'] = $physician->id;
                $data['email'] = $physician->email;
                $data['type'] = EmailSetup::LOG_ENTRY_REMINDER_FOR_PHYSICIANS;
                $data['with'] = [
                    'name' => $physician->first_name,
                    'last_name' => $physician->last_name
                ];
                $data['subject_param'] = [
                    'name' => '',
                    'date' => date("F", strtotime("-1 month", strtotime(date("F")))) . ' ' . date("Y", strtotime("-1 month", strtotime(date("F")))),
                    'month' => '',
                    'year' => '',
                    'requested_by' => '',
                    'manager' => '',
                    'subjects' => ''
                ];
                if ($device_token) {
                    foreach ($device_token as $tokens) {
                        $deviceToken = $tokens->device_token;

                        // OneSignal push notification code is added here by akash
                        $push_msg = 'Please enter log for month of ' . date("F", strtotime("-1 month", strtotime(date("F"))));
                        $notification_for = 'REMINDER';
                        $message = [
                            //'title' => 'This is the title',
                            //'body' => 'This is the body'
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
                            'message' => 'Please enter log for month of ' . date("F", strtotime("-1 month", strtotime(date("F"))))
                        ];

                        try {
                            if ($tokens->device_type == PhysicianDeviceToken::iOS) {
                                $title = 'Please enter log for month of ' . date("F", strtotime("-1 month", strtotime(date("F"))));
                                $body = 'Please enter log for month of ' . date("F", strtotime("-1 month", strtotime(date("F"))));
                                $result = NotificationService::sendPushNotificationForIOS($deviceToken, $title, $body);
                            } elseif ($tokens->device_type == PhysicianDeviceToken::Android) {
                                $result = NotificationService::sendPushNotificationForAndroid($deviceToken, $message);
                            }
                            $result = NotificationService::sendOneSignalPushNotification($deviceToken, $push_msg, $notification_for);
                        } catch (Exception $e) {
                            Log::info("error", array($e));
                        }
                    }
                }
                if ($data['email'] != 'hrushikesh@biz4solutions.com') {
                    try {
                        EmailQueueService::sendEmail($data);

                        sleep(5);
                    } catch (Swift_TransportException $e) {
                        Log::info("failed:", array($data['email']));
                        Mail::getSwiftMailer()->getTransport()->stop();
                        sleep(20); // Just in case ;-)
                    }
                }
            }
        }
    }

    public function approveLog()
    {
        $physicians = Physician::all();
        foreach ($physicians as $physician) {
            $contracts = new Contract();
            $pending_logs = $contracts->getUnapproveLogs($physician);
            if (count($pending_logs) > 0) {
                $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->get();
                $data['name'] = $physician->first_name;
                $data['last_name'] = $physician->last_name;
                $data['physician'] = $physician->id;
                $data['email'] = $physician->email;
                if ($device_token) {
                    foreach ($device_token as $tokens) {
                        $deviceToken = $tokens->device_token;

                        try {
                            if ($tokens->device_type == PhysicianDeviceToken::iOS) {
                                $title = 'Please approve logs for previous month';
                                $body = 'Please approve logs for previous month.';

                                $result = NotificationService::sendPushNotificationForIOS($deviceToken, $title, $body);
                            } elseif ($tokens->device_type == PhysicianDeviceToken::Android) {

                                $message = [
                                    'extraPayLoad1' => 'value1',
                                    'extraPayLoad2' => 'value2',
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
                                    'message' => 'Please approve logs for previous month'
                                ];

                                $result = NotificationService::sendPushNotificationForAndroid($deviceToken, $message);
                            }
                        } catch (Exception $e) {
                            Log::info("error", array($e));
                        }
                    }
                }
            }
        }
    }
}

?>
