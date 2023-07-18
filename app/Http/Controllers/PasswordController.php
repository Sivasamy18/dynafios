<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request as HttpReq;
use App\Group;
use App\Physician;
use App\User;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\UserValidation;

class PasswordController extends BaseController
{
    public function getRemind()
    {
        if (Auth::check()) {
            return Redirect::route('dashboard.index');
        }

        return View::make('password/remind');
    }

    public function postRemind()
    {
        $email = Request::only('email');

        // $response = Password::remind($email, function ($message) { //Old line of code
        // $response = Password::sendResetLink($email, function ($message) {
        //     // $message->subject('Password Reset'); //Old
        //     $message->only('Password Reset');
        // });

        $response = Password::sendResetLink($email);

        switch ($response) {
            case Password::INVALID_USER:
                return Redirect::back()
                    ->with('error', Lang::get($response))
                    ->withInput();

            // case Password::REMINDER_SENT: //Original line
            case Password::RESET_LINK_SENT: //Newly added line
                return Redirect::back()
                    ->with('success', Lang::get($response))
                    ->withInput();
        }
    }

    public function getReset(HttpReq $request, $token = null)
    {
        if (is_null($token))
            App::abort(404);

        // $email = DB::table(Config::get('auth.reminder.table'))
        //     ->where('token', $token)
        //     ->pluck('email');

        $req_obj = $request->all();
        if (isset($req_obj['email']) && $token != null) {
            $email = $req_obj['email'];

            return View::make('password/reset')->with(array('token' => $token, 'email' => $email));
        } else {
            return Redirect::route('password.remind')->with('error', 'Please check the valid link provided in email.');
        }
    }

    public function postReset($token = null)
    {
        $credentials = Request::only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $lock_check = User::where("email", "=", Request::only('email')['email'])->first();

        if ($lock_check) {
            if ($lock_check->getLocked() == 1) {
                return Redirect::back()->with([
                    'error' => Lang::get("auth.account_locked")
                ])->withInput();
            }
        }

        // Password::validator(function () {
        $user = User::where("email", "=", Request::only('email')['email'])->first();
        if (Hash::check(Request::only('password')['password'], $user->password)) {
            // return false;
            return Redirect::back()->with('error', 'Please use another password.');
        }
        $validation = new UserValidation();
        if (!$validation->validateRemind(Request::only('password'))) {
            // return false;
            return Redirect::back()->with('error', 'Password must be between 8 and 20 characters in length, match the confirmation, cannot be the same as your current password, and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).');
        } else if (Request::only('password')['password'] == '') {
            // return true;
            return Redirect::back()->with('error', 'Please enter valid password and confirm password.');
        } else if (Request::only('password_confirmation')['password_confirmation'] == '') {
            // return true;
            return Redirect::back()->with('error', 'Please enter valid password and confirm password.');
        } else if (Request::only('password')['password'] != Request::only('password_confirmation')['password_confirmation']) {
            // return true;
            return Redirect::back()->with('error', 'Password and confirm password must be same.');
        }
        // });

        $response = Password::reset($credentials, function ($user, $password) {
            $user->password = Hash::make($password);
            $user->setPasswordText($password);
            $user->save();
            //reset password for physician in physician table
            if ($user->group_id == Group::Physicians) {
                $physician = Physician::where("email", "=", $user->email)->first();
                $physician->password = Hash::make($password);
                $physician->setPasswordText($password);
                $physician->save();
            }
            event(new PasswordReset($user));
        });

        switch ($response) {
            // case Password::INVALID_PASSWORD:
            //     return Redirect::back()->with('error', 'Password must be between 8 and 20 characters in length, match the confirmation, cannot be the same as your current password, and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).');
            case Password::INVALID_TOKEN:
            case Password::INVALID_USER:
                return Redirect::back()->with('error', Lang::get($response));

            case Password::PASSWORD_RESET:
                return Redirect::route('auth.login')
                    ->with('success', Lang::get('reminders.success'));

        }
    }
}
