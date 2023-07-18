<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    private function emailSupportTeam($exception_data)
    {
        try {
            $email_exceptions = config('exceptions.email_exceptions');
            if ($email_exceptions) {
                $email_data = [
                    'email' => 'support@dynafiosapp.com',
                    'name' => 'Dynafios Support Team',
                    'type' => EmailSetup::EMAIL_EXCEPTION_SUPPORT_TEAM,
                    'with' => $exception_data,
                    'subject' => 'Exception Reacted by Customer ( '.$exception_data['error_code'].' ) Oops page'
                ];

                EmailQueueService::sendEmail($email_data);
            }
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }
    }

    private function logException($exception_data)
    {
        try {
            $log_exceptions = config('exceptions.log_exceptions');
            if ($log_exceptions) {
                $error_details = 'Problem accessing dynafios URL: User with id '.$exception_data['user_id'].' received http error '.$exception_data['error_code'].' accessing page "'.$exception_data['path'].'.';
                if (!empty($exception_data['error_message'])) {
                    $error_details = $error_details.'" Error description: '.$exception_data['error_message'].'.';
                }
                Log::error($error_details);
            }
        } catch (Throwable $e) {
        }
    }


    public function report($exception)
    {
        try {
            $error_code = null;

            try {
                $error_code = $exception->getStatusCode();
            } catch (Throwable $e) {
            }

            $error_codes_to_email = config('exceptions.email.error_codes');
            
            try{
                $path = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            }catch(Throwable $e){
                $path = 'No path';
            }

            $data = [
                'path' => $path,
                'exception_stack_trace' => $exception->getTraceAsString(),
                'exception_class' => get_class($exception),
                'error_code' => $error_code,
                'error_message' => $exception->getMessage(),
                'user' => null,
                'user_first_name' => null,
                'user_last_name' => null,
                'user_email' => null,
                'user_id' => null,
                'input' => json_encode(Request::input()),
            ];

            if (Auth::check()) {
                $user = Auth::user();
                $data['user'] = $user;
                $data['user_email'] = $user->email;
                $data['user_first_name'] = $user->first_name;
                $data['user_last_name'] = $user->last_name;
                $data['user_id'] = $user->id;
            }

            if (empty($error_code)) {
                // $this->logException($data);
                $this->emailSupportTeam($data);
            } else if (is_array($error_codes_to_email)) {
                if (in_array($error_code, $error_codes_to_email)) {
                    // $this->logException($data);
                    $this->emailSupportTeam($data);
                }
            }

        } catch (Throwable $e) {
            Log::error('Reporting Oops page to support Team:  '.$e->getMessage());
        }

        return parent::report($exception);
    }
}