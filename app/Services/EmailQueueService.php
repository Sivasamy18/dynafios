<?php

namespace App\Services;

use App\Models\MailTracker;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Mail\EmailMessage;

class EmailQueueService
{
    public static function sendEmail($data, $user = null)
    {
        $name = null;
        $possiblePlacesWeStoreName = [
            $data['name'],
            $data['email'],
            $data['with']['name'] = array_key_exists('name', $data['with']) ? $data['with']['name'] : $data['name'],
            $data['with']['email'] = array_key_exists('email', $data['with']) ? $data['with']['email'] : $data['email'],
        ];

        foreach ($possiblePlacesWeStoreName as $possibility) {
            if ($possibility === "") {
                break;
            } else {
                $name = $possibility;
            }
        }

        MailTracker::create([
            'user_id' => $user->id ?? null,
            'sent_to' =>  $name ?? 'N/A',
            'subject' => $data['subject'] ?? 'N/A',
            'message' => $data['message'] ?? 'N/A',
        ]);

        Mail::to($data['email'])->queue(new EmailMessage($data));
    }
}
