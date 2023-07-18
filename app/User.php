<?php

namespace App;

use App\Models\Files\File;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;
use App\Http\Controllers\Validations\EmailValidation;
use Request;
use Hash;
use DateTime;
use Redirect;
use App\Start;
use Log;
use App\Http\Controllers\Validations\UserValidation;
use Lang;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, Auditable
{

    use Authenticatable,
        Authorizable,
        CanResetPassword,
        SoftDeletes,
        Notifiable,
        HasRoles,
        \OwenIt\Auditing\Auditable,
        HasFactory;

    protected $table = 'users';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];
    protected $hidden = array('password');
    protected $guarded = [];

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function getUnsuccessfulLoginAttempts()
    {
        return $this->unsuccessful_login_attempts;
    }

    public function setUnsuccessfulLoginAttempts($value)
    {
        $this->unsuccessful_login_attempts = $value;
    }

    public function getUnsuccessfulLoginAttemptsName()
    {
        return 'unsuccessful_login_attempts';
    }

    public function getLocked()
    {
        return $this->locked;
    }

    public function setLocked($value)
    {
        $this->locked = $value;
    }

    public function getLockedName()
    {
        return 'locked';
    }

    public function getPasswordExpirationDate()
    {
        return $this->password_expiration_date;
    }

    public function setPasswordText($value)
    {
        $this->attributes['password_text'] = $value;
        // $this->attributes['password_text'] = Crypt::encryptString($value);
    }

    public function getPasswordText()
    {
        return $this->attributes['password_text'];
        // return Crypt::decryptString($this->attributes['password_text']);
    }


    public static function postCreate()
    {
        $validation = new UserValidation();
        $emailvalidation = new EmailValidation();
        if (!$validation->validateCreate(Request::input())) {
            if ($validation->messages()->has('email') && Request::input('email') != '') {
                $deletedUser = self::where('email', '=', trim(Request::input('email')))->onlyTrashed()->first();
                if ($deletedUser) {
                    $validation->messages()->add('emailDeleted', 'User with this email already exist, you can request administrator to restore it.');
                }
            }
            return ["status" => false,
                "validation" => $validation];
        }

        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return ["status" => false,
                "validation" => $emailvalidation];
        }


        $randomPassword = randomPassword();
        $user = new User();
        $user->email = Request::input('email');
        $user->first_name = Request::input('first_name');
        $user->last_name = Request::input('last_name');
        $user->title = Request::input('title');
        $user->initials = strtoupper("{$user->first_name[0]}{$user->last_name[0]}");
        $user->phone = Request::input('phone');
        $user->group_id = Request::input('group');
        $user->setPasswordText($randomPassword);
        $user->password = Hash::make($randomPassword);
        $user->seen_at = date("Y-m-d H:i:s");

        //$expiration_date = new DateTime("+12 months");
        $expiration_date = new DateTime('1999-01-01');
        $user->password_expiration_date = $expiration_date;

        if (!$user->save()) {
            return ["status" => false];
        }

        $data = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'password' => $user->password_text
        ];

        //Remove as per request 31 Dec 2018 by 1101
        /*Mail::send('emails/users/create', $data, function ($message) use ($data) {
            $message->to($data['email'], $data['name']);
            $message->subject('User Account');
        });*/

        return ["status" => true,
            "data" => $user];
    }

    public static function getRestore($id)
    {
        $user = self::onlyTrashed()->where('id', $id)->first();

        if (!$user->restore()) {
            return Redirect::back()->with([
                'error' => Lang::get('users.restore_error')
            ]);
        }

        return Redirect::route('users.show', $user->id)->with([
            'success' => Lang::get('users.restore_success')
        ]);
    }

    public static function getHospitalUsersByUserId($id)
    {
        $hospital_id = Hospital::select('hospitals.id')
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where("hospital_user.user_id", "=", $id)->first();

        if ($hospital_id != null) {
            $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                ->where('hospital_user.hospital_id', '=', $hospital_id['id'])
                ->where('users.id', '!=', $id)
                ->where('group_id', '!=', Group::Physicians)
                ->orderBy("name")
                ->pluck('name', 'id')->toArray();

            return $users;
        } else {
            return (array)[];
        }

    }

    public static function getHospitalUsersByHospitalId($hospital_id)
    {

        if ($hospital_id != null) {
            $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                //->where('hospital_user.hospital_id', '=', $physician->practice->hospital_id)
                ->where('hospital_user.hospital_id', '=', $hospital_id)
                ->where('group_id', '!=', Group::Physicians)
                ->orderBy("name")
                ->pluck('name', 'id');

            return $users;
        } else {
            return (array)[];
        }

    }


    public function getHospitalPasswordExpirationDate($user_id)
    {
        $hospital_password_expiration_months = DB::table("hospital_user")
            ->select("hospitals.password_expiration_months as password_expiration_months")
            ->join("hospitals", "hospitals.id", "=", "hospital_user.hospital_id")
            ->where("hospital_user.user_id", "=", $user_id)
            ->pluck('password_expiration_months');
        $date = new DateTime("+" . max($hospital_password_expiration_months->toArray()) . " months");
        if ($date) {
            return $date;
        } else {
            return 12;
        }
    }

    public function getPracticeHospitalPasswordExpirationDate($user_id)
    {
        $hospital_password_expiration_months = DB::table("practice_user")
            ->select("hospitals.password_expiration_months as password_expiration_months")
            ->join("practices", "practices.id", "=", "practice_user.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->where("practice_user.user_id", "=", $user_id)
            ->pluck('password_expiration_months');
        $date = new DateTime("+" . max($hospital_password_expiration_months->toArray()) . " months");
        if ($date) {
            return $date;
        } else {
            return 12;
        }
    }

    public function getPhysicianHospitalPasswordExpirationDate($user_id)
    {
        //drop column practice_id from table 'physicians' changes by 1254 : updated query with physican_practices table
        $hospital_password_expiration_months = DB::table("users")
            ->select("hospitals.password_expiration_months as password_expiration_months")
            ->join("physicians", "physicians.email", "=", "users.email")
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->join("practices", "practices.id", "=", "physician_practices.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->where("users.id", "=", $user_id)
            ->where("users.group_id", "=", Group::Physicians)
            ->pluck('password_expiration_months');
        $date = new DateTime("+" . max($hospital_password_expiration_months->toArray()) . " months");
        if ($date) {
            return $date;
        } else {
            return 12;
        }
    }

    public function setPasswordExpirationDate($value)
    {
        $this->password_expiration_date = $value;
    }

    public function getPasswordExpirationDateName()
    {
        return 'password_expiration_date';
    }

    public function getReminderEmail()
    {
        return $this->email;
    }

    public function group()
    {
        return $this->belongsTo('App\Group');
    }

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

    public function ticket_messages()
    {
        return $this->hasMany('App\TicketMessage');
    }

    public function hospitals()
    {
        return $this->belongsToMany('App\Hospital');
    }

    public function practices()
    {
        return $this->belongsToMany('App\Practice');
    }

    public function getFullName()
    {
        return "{$this->first_name} {$this->last_name}";
    }


}
