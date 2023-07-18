<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Response;
use Exception;
use Mail;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;

class OtpHistory extends Model
{
    protected $table = 'otp_history';
    const STATUS_FAILURE = 0;
    const STATUS_SUCCESS = 1;

    public static function saveOTP($physician, $otp_type)
    {
        if ($physician) {
            // generate a 6 digit unique number
            $generate_otp = random_int(100000, 999999);
            $email = $physician->email;
            $name = $physician->first_name . ' ' . $physician->last_name;

            self::where('user_id', $physician->id)
                ->where('email', '=', $physician->email)
                ->where('otp_type', '=', $otp_type)
                ->update(['is_active' => 0]);

            $now = time();
            $expire_time = $now + (2 * 60);
            $otp_expiray_date = date('Y-m-d H:i:s', $expire_time);

            $otp_history = new OtpHistory;
            $otp_history->user_id = $physician->id;
            $otp_history->email = $physician->email;
            $otp_history->otp = $generate_otp;
            $otp_history->otp_type = $otp_type;
            $otp_history->otp_expiry_date = $otp_expiray_date;
            $otp_history->save();

            if (!$otp_history) {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("An error occurred while saving the OTP.")
                ]);
            } else {
                $html = "<p>Your verification code is - $generate_otp </p>";
                $data = [
                    'email' => $email,
                    'name' => $name,
                    'type' => EmailSetup::SEND_OTP,
                    'with' => [
                        'otp' => $generate_otp,
                        'name' => $name
                    ]
                ];
                try {
                    EmailQueueService::sendEmail($data);
                } catch (Exception $e) {
                    Log::info("Send otp to email Catch Error" . $e->getMessage());
                }
            }

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("We have emailed you an OTP!")
            ]);
        } else {
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("A link to reset password has been sent to this email address.")
            ]);
        }
    }

    public static function verifyOTP($physician, $otp, $otp_type)
    {
        if ($physician) {
            //date_default_timezone_set('Asia/Kolkata');
            $current_date = date('Y-m-d H:i:s');

            $otp_history = self::where('user_id', $physician->id)
                ->where('email', '=', $physician->email)
                ->where('otp', '=', $otp)
                ->where('is_active', '=', 1)
                ->where('otp_type', '=', $otp_type)
                ->where('otp_expiry_date', '>=', $current_date)
                ->first();

            if ($otp_history) {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("OTP Verified successfully.")
                ]);
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("Please enter a valid OTP.")
                ]);
            }
        } else {
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("A link to reset password has been sent to this email address.")
            ]);
        }
    }
}
