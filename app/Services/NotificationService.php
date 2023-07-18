<?php

namespace App\Services;

use Edujugon\PushNotification\PushNotification;
use Berkayk\OneSignal\OneSignalFacade as OneSignal;
use Illuminate\Support\Facades\Log;


class NotificationService
{
    /**
     * @param $token
     * @param $title
     * @param $body
     * @return void
     */
    public static function sendPushNotificationForIOS($token, $title, $body)
    {

        $push = new PushNotification('apn');
        $result = $push->setMessage([
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'sound' => 'default',
                'badge' => 1

            ],
            'extraPayLoad' => [
                'custom' => 'My custom data',
            ]
        ])
            ->setDevicesToken([$token])
            ->send()
            ->getFeedback();

        // Log::info("response IOS:", array($result));
    }

    public static function sendPushNotificationForAndroid($token, $body)
    {

        $push = new PushNotification('fcm');

        $result = $push->setMessage([
            'data' => $body
        ])
            //->setApiKey('AIzaSyCQMPgP2mXZvOrvUt7rXu5-_yOIemo64LA')
            ->setDevicesToken([$token])
            ->send()
            ->getFeedback();

        // Log::info("response Android:", array($result));
    }

    public static function sendOneSignalPushNotification($deviceToken, $push_msg, $notification_for)
    {
        $res = OneSignal::sendNotificationToUser(
            $push_msg,
            // $userId,
            $deviceToken,
            // '51aa9b5a-31e5-4169-8591-c016adc09e47',
            $url = null,
            $dataOne = ['notification_type' => $notification_for],
            $buttons = null,
            $schedule = null
        );
//        Log::Info('postUnapproveLogs OneSignal sent for ' . $notification_for);
    }
}
