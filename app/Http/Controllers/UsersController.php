<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\ApprovalManagerInfo;
use App\Physician;
use App\ProxyApprovalDetails;
use App\Hospital;
use Illuminate\Support\Facades\Log;
use DateTime;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\UserValidation;
use function App\Start\is_health_system_region_user;
use function App\Start\is_health_system_user;
use function App\Start\is_owner;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;


class UsersController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = false;
    protected $requireSuperUserOptions = [
        'except' => ['getShow', 'getEdit', 'postEdit', 'getExpired', 'postExpired', 'getProxy', 'postProxyUser', 'getDeleteProxy']
    ];

    public function getIndex()
    {
        $options = [
            'filter' => Request::input('filter'),
            'sort' => Request::input('sort', 7),
            'order' => Request::input('order', 2),
            'sort_min' => 2,
            'sort_max' => 7,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['id', 'email', 'last_name', 'first_name', 'group_id', 'password_text', 'created_at'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        $data = $this->queryWithUnion('User', $options, function ($query, $options) {
            switch ($options['filter']) {
                case 1:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->where('users.deleted_at', '=', null)
                        ->where('group_id', '=', Group::SUPER_USER)
                        ->distinct();
                case 2:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('users.deleted_at', '=', null)
                        ->where('group_id', '=', Group::HOSPITAL_ADMIN)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct();
                case 5:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('users.deleted_at', '=', null)
                        ->where('group_id', '=', Group::SUPER_HOSPITAL_USER)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct();
                case 3:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("practice_user", "users.id", "=", "practice_user.user_id")
                        ->join("practices", "practice_user.practice_id", "=", "practices.id")
                        ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                        ->where('users.deleted_at', '=', null)
                        ->where('group_id', '=', Group::PRACTICE_MANAGER)
                        ->where('hospitals.archived', '=', 0)
                        ->distinct();
                case 7:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("health_system_users", "users.id", "=", "health_system_users.user_id")
                        ->where('users.deleted_at', '=', null)
                        ->where('group_id', '=', Group::HEALTH_SYSTEM_USER)
                        ->distinct();
                case 8:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("health_system_region_users", "users.id", "=", "health_system_region_users.user_id")
                        ->where('deleted_at', '=', null)
                        ->where('group_id', '=', Group::HEALTH_SYSTEM_REGION_USER)
                        ->distinct();
                default:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->where('deleted_at', '=', null)
                        ->where('group_id', '=', Group::SUPER_USER)
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                                ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                                ->where('group_id', '=', Group::HOSPITAL_ADMIN)
                                ->where('users.deleted_at', '=', null)
                                ->where('hospitals.archived', '=', 0)
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                                ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                                ->where('group_id', '=', Group::SUPER_HOSPITAL_USER)
                                ->where('users.deleted_at', '=', null)
                                ->where('hospitals.archived', '=', 0)
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("practice_user", "users.id", "=", "practice_user.user_id")
                                ->join("practices", "practice_user.practice_id", "=", "practices.id")
                                ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                                ->where('group_id', '=', Group::PRACTICE_MANAGER)
                                ->where('users.deleted_at', '=', null)
                                ->where('hospitals.archived', '=', 0)
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("health_system_users", "users.id", "=", "health_system_users.user_id")
                                ->where('group_id', '=', Group::HEALTH_SYSTEM_USER)
                                ->where('users.deleted_at', '=', null)
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("health_system_region_users", "users.id", "=", "health_system_region_users.user_id")
                                ->where('group_id', '=', Group::HEALTH_SYSTEM_REGION_USER)
                                ->where('users.deleted_at', '=', null)
                                ->distinct()
                        )
                        ->orderBy($options['field_names'][$options['sort'] - 1], $options['order'] == 1 ? 'asc' : 'desc')
                        ->distinct();
            }
        });


        $data['table'] = View::make('users/_users')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('users/index')->with($data);
    }

    public function getIndexShowAll()
    {
        $options = [
            'filter' => Request::input('filter'),
            'sort' => Request::input('sort', 7),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 7,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) {
            switch ($options['filter']) {
                case 1:
                    return $query->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')->where('group_id', '=', Group::SUPER_USER);
                case 2:
                    return $query->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')->where('group_id', '=', Group::HOSPITAL_ADMIN);
                case 5:
                    return $query->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')->where('group_id', '=', Group::SUPER_HOSPITAL_USER);
                case 3:
                    return $query->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')->where('group_id', '=', Group::PRACTICE_MANAGER);
                default:
                    return $query->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'users.group_id', 'users.password_text', 'users.created_at')->where('group_id', '!=', Group::Physicians);
            }
        });

        $data['table'] = View::make('users/_users')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('users/index_show_all')->with($data);
    }

    public function getCreate()
    {
        $groups = options(Group::whereNotIn('id', [Group::HEALTH_SYSTEM_USER, Group::HEALTH_SYSTEM_REGION_USER])->get(), 'id', 'name');

        return View::make('users/create')->with(['groups' => $groups]);
    }

    public function postCreate()
    {
        $result = User::postCreate();
        if ($result["status"]) {
            return Redirect::route('users.index')->with(['success' => Lang::get('users.create_success')]);
        } else {
            if (isset($result["validation"])) {
                return Redirect::back()->withErrors($result["validation"]->messages())->withInput();
            } else {
                return Redirect::back()->with(['error' => Lang::get('users.create_error')]);
            }
        }
    }

    public function getShow($id, $hospital_id = 0)
    {
        $user = User::findOrFail($id);

        if (!is_super_user() && !is_owner($user->id) && !is_super_hospital_user()) {
            App::abort(403);
        }

        if (Auth::user()->group_id == Group::Physicians) {
            $physician = Physician::where('email', '=', $this->currentUser->email)->first();
            $physician->practice_id = Request::has("p_id") ? Request::Input("p_id") : 0;
            return View::make('users/show')->with(['user' => $user, 'physician' => $physician]);
        } else {
            if (is_super_hospital_user()) {
                return View::make('users/show')->with(['user' => $user, 'hospital_id' => $hospital_id]);
            } else {
                return View::make('users/show')->with(['user' => $user]);
            }

        }
    }

    public function getResetPassword($id)
    {
        $user = User::findOrFail($id);

        /*if (!is_super_user()|| !is_super_hospital_user())
            App::abort(403);*/
        $randomPassword = randomPassword();
        $user->setPasswordText($randomPassword);
        $user->password = Hash::make($randomPassword);

        if (!$user->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('user.reset_error')
            ]);
        }

        $data = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'password' => $user->password_text,
            'type' => EmailSetup::USER_RESET_PASSWORD,
            'with' => [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $user->password_text
            ],
        ];

        EmailQueueService::sendEmail($data);

        return Redirect::back()->with([
            'success' => Lang::get('users.reset_success')
        ]);

    }

    public function getEdit($id, $hospital_id = 0)
    {
        $user = User::findOrFail($id);
        $physician = null;
        if (!is_super_user() && !is_owner($user->id) && !is_super_hospital_user()) {
            App::abort(403);
        }

        if (Auth::user()->group_id == Group::Physicians) {
            $physician = Physician::where('email', '=', $this->currentUser->email)->first();
            $physician->practice_id = Request::has("p_id") ? Request::Input("p_id") : 0;
        }

        return View::make('users/edit')->with([
            'user' => $user,
            'physician' => $physician,
            'hospital_id' => $hospital_id,
            'groups' => Group::pluck('name', 'id')
        ]);
    }

    public function getProxy($id)
    {
        $user = User::findOrFail($id);
        if (!is_super_user() && !is_owner($user->id)) {
            App::abort(403);
        }

        $default = ['0' => 'Select Approver'];
        $users = $default + User::getHospitalUsersByUserId($id);

        $check_for_proxy_approver = ProxyApprovalDetails::find_only_proxy_aaprover_users($id);
        if ($check_for_proxy_approver) {
            $proxy_approver_id = $check_for_proxy_approver->proxy_approver_id;
            $proxy_approver_start_date = format_date($check_for_proxy_approver->start_date);
            $proxy_approver_end_date = format_date($check_for_proxy_approver->end_date);
        } else {
            $proxy_approver_id = 0;
            $proxy_approver_start_date = format_date(date('m/d/Y'));
            $proxy_approver_end_date = format_date(date('m/d/Y'));
        }

        return View::make('users/add_proxy_approver')->with([
            'user' => $user,
            'users' => $users,
            'proxy_approver_id' => $proxy_approver_id,
            'proxy_approver_start_date' => $proxy_approver_start_date,
            'proxy_approver_end_date' => $proxy_approver_end_date,
            'groups' => Group::pluck('name', 'id')
        ]);
    }

    public function postProxyUser($id)
    {
        $userid = $id;
        $proxy_user_id = Request::input('approval_manager');
        $start_date = mysql_date(Request::input('start_date'));
        $end_date = mysql_date(Request::input('end_date'));
        $created_by = Auth::user()->id;
        $proxy_approver_details = ProxyApprovalDetails::save_proxy_aaprover_user($userid, $proxy_user_id, $start_date, $end_date, $created_by);

        if ($proxy_approver_details["response"] === "error") {
            return Redirect::back()->with([
                'error' => $proxy_approver_details["msg"]
            ])->withInput();
        } else {
            return Redirect::route('users.add_proxy', $id)
                ->with(['success' => $proxy_approver_details["msg"]]);
        }
    }

    public function getDeleteProxy($id)
    {
        $check_for_proxy_approver = ProxyApprovalDetails::delete_proxy_user($id);
        if (!($check_for_proxy_approver)) {
            return Redirect::back()->with([
                'error' => Lang::get('proxy_user.delete_error')
            ]);
        } else {
            return Redirect::route('users.add_proxy', $id)->with([
                'success' => Lang::get('proxy_user.delete_success')
            ]);
        }
    }

    public function postEdit($id, $hospital_id = 0)
    {
        $user = User::findOrFail($id);

        $validation = new UserValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $user->group_id = Request::input('group', $user->group_id);
        $user->email = Request::input('email', $user->email);
        $user->first_name = Request::input('first_name');
        $user->last_name = Request::input('last_name');
        $user->title = Request::input('title');
        $user->initials = strtoupper("{$user->first_name[0]}{$user->last_name[0]}");
        $user->phone = Request::input('phone');
        $user->locked = Request::input('locked');
        if ($user->locked == 0) {
            $user->unsuccessful_login_attempts = 0;
        }

        if (Request::has('new_password') && Request::input('new_password') != '') {
            if (!is_super_user() || is_owner($user->id)) {
                if (!Hash::check(Request::input('current_password'), $user->password)) {
                    return Redirect::back()->with(['error' => 'You have entered an invalid password for your current password.']);
                }
                if (Hash::check(Request::input('new_password'), $user->password)) {
                    return Redirect::back()->with(['error' => 'Your new password must be different than your current password.']);
                }
            }

            $user->setPasswordText(Request::input('new_password'));
            $user->password = Hash::make(Request::input('new_password'));
        }

        if (!$user->save()) {
            return Redirect::back()
                ->with(['error' => 'An error occurred while updating the user profile.'])
                ->withInput();
        } else {
            if($user->group_id == Group::Physicians){
                $physicians = Physician::where('email','=',$user->email)->first();

                if($physicians){
                    $physicians->password_text = $user->password_text;
                    $physicians->password = $user->password;
                    $physicians->save();
                }
            }
        }

        if (is_super_user()) {
            return Redirect::route('users.show', $user->id)->with(['success' => 'Successfully updated user profile.']);
        } elseif (is_super_hospital_user()) {
            return Redirect::route('users.adminshow', [$user->id, $hospital_id])->with(['success' => 'Successfully updated user profile.']);
        } else {
            return Redirect::route('users.show', $user->id)->with(['success' => 'Successfully updated user profile.']);
        }
    }

    public function getExpired($id)
    {
        $user = User::findOrFail($id);

        if (!is_super_user() && !is_owner($user->id)) {
            App::abort(403);
        }

        return View::make('users/expired')->with([
            'user' => $user,
            'groups' => Group::pluck('name', 'id')
        ]);
    }

    public function postExpired($id)
    {
        $user = User::findOrFail($id);

        $validation = new UserValidation();
        if (!$validation->validatePassword(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        if (Request::has('new_password')) {
            if (!is_super_user() || is_owner($user->id)) {
                if (!Hash::check(Request::input('current_password'), $user->password)) {
                    return Redirect::back()->with(['error' => 'You have entered an invalid password for your current password.']);
                }
                if (Hash::check(Request::input('new_password'), $user->password)) {
                    return Redirect::back()->with(['error' => 'Your new password must be different than your current password.']);
                }
            }

            $user->setPasswordText(Request::input('new_password'));
            $user->password = Hash::make(Request::input('new_password'));
            if (is_super_user() || is_health_system_user() || is_health_system_region_user()) {
                $expiration_date = new DateTime("+12 months");
                $user->password_expiration_date = $expiration_date;
            } elseif ($user->group_id == Group::PRACTICE_MANAGER) {
                $expiration_date = $user->getPracticeHospitalPasswordExpirationDate($user->id);
                if (!$expiration_date) {
                    $expiration_date = new DateTime("+12 months");
                }
                $user->password_expiration_date = $expiration_date;
            } else {
                $expiration_date = $user->getHospitalPasswordExpirationDate($user->id);
                if (!$expiration_date) {
                    $expiration_date = new DateTime("+12 months");
                }
                $user->password_expiration_date = $expiration_date;
            }
        }

        if (!$user->save()) {
            return Redirect::back()
                ->with(['error' => 'An error occurred while updating the user profile.'])
                ->withInput();
        }

        if (Request::has('new_password')) {
            return Redirect::route('auth.logout')->with(['success' => 'Password successfully changed. Please login with your new password.']);
        }
    }

    public function getDelete($id, $hospital_id = 0)
    {
        $user = User::findOrFail($id);
        $check = ApprovalManagerInfo::join('agreements', 'agreements.id', '=', 'agreement_approval_managers_info.agreement_id')
            ->where('agreement_approval_managers_info.user_id', '=', $id)
            ->where('agreements.is_deleted', '=', 0)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')->get();
        if (count($check) > 0) {
            return Redirect::back()->with([
                'error' => Lang::get('users.assigned_error')
            ]);
        }

        if (!$user->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('users.delete_error')
            ]);
        } else {
            $user->tickets()->delete();
            $user->ticket_messages()->delete();

            /*
             * Delete proxy users for the deleting user_id
             */
            $today = mysql_date(date('Y-m-d'));
            $proxy_check = ProxyApprovalDetails::where("proxy_approver_details.user_id", "=", $id)
                ->orWhere("proxy_approver_details.proxy_approver_id", "=", $id)
                ->where("proxy_approver_details.start_date", "<=", $today)
                ->where("proxy_approver_details.end_date", ">=", $today)
                ->whereNull("proxy_approver_details.deleted_at")
                ->get();

            if (count($proxy_check) > 0) {
                foreach ($proxy_check as $proxy_check_obj) {
                    $proxy_check_obj->delete();
                }
            }
        }

        if (is_super_hospital_user()) {
            return Redirect::route('hospitals.admins', $hospital_id)->with([
                'success' => Lang::get('users.delete_success')
            ]);
        } else {
            return Redirect::route('users.index')->with([
                'success' => Lang::get('users.delete_success')
            ]);
        }


    }


    public function getWelcome($id)
    {
        $user = User::findOrFail($id);
        $hospital_names = array();
        foreach ($user->hospitals as $hospital) {
            $hospital_names[] = $hospital->name;
        }
        $data = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'password' => $user->password_text,
            'hospitals_name' => $hospital_names,
            'type' => EmailSetup::USER_WELCOME,
            'with' => [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $user->password_text,
                'hospitals_name' => $hospital_names
            ]
        ];

        EmailQueueService::sendEmail($data);

        return Redirect::back()->with([
            'success' => Lang::get('users.welcome_success')
        ]);
    }

    public function getDeleted()
    {
        $options = [
            'filter' => Request::input('filter'),
            'sort' => Request::input('sort', 8),
            'order' => Request::input('order', 2),
            'sort_min' => 2,
            'sort_max' => 8,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['id', 'email', 'last_name', 'first_name', 'hospital_name', 'group_id', 'password_text', 'created_at']
        ];

        $data = $this->queryWithUnion('User', $options, function ($query, $options) {
            switch ($options['filter']) {
                case 1:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->leftJoin("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->leftJoin("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('users.group_id', '=', Group::SUPER_USER)
                        ->onlyTrashed()
                        ->distinct();
                case 2:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('users.group_id', '=', Group::HOSPITAL_ADMIN)
                        ->onlyTrashed()
                        ->distinct();
                case 5:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('users.group_id', '=', Group::SUPER_HOSPITAL_USER)
                        ->onlyTrashed()
                        ->distinct();
                case 3:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->join("practice_user", "users.id", "=", "practice_user.user_id")
                        ->join("practices", "practice_user.practice_id", "=", "practices.id")
                        ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                        ->where('users.group_id', '=', Group::PRACTICE_MANAGER)
                        ->onlyTrashed()
                        ->distinct();
                default:
                    return $query
                        ->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                        ->leftJoin("hospital_user", "users.id", "=", "hospital_user.user_id")
                        ->leftJoin("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                        ->where('group_id', '=', Group::SUPER_USER)
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                                ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                                ->where('users.group_id', '=', Group::HOSPITAL_ADMIN)
                                ->where(function ($query1) {
                                    $query1->whereNotNull('users.deleted_at')
                                        ->orWhere('users.deleted_at', '!=', '');
                                })
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
                                ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
                                ->where('users.group_id', '=', Group::SUPER_HOSPITAL_USER)
                                ->where(function ($query1) {
                                    $query1->whereNotNull('users.deleted_at')
                                        ->orWhere('users.deleted_at', '!=', '');
                                })
                                ->distinct()
                        )
                        ->union(
                            DB::table('users')->select('users.id', 'users.email', 'users.last_name', 'users.first_name', 'hospitals.name as hospital_name', 'users.group_id', 'users.password_text', 'users.created_at')
                                ->join("practice_user", "users.id", "=", "practice_user.user_id")
                                ->join("practices", "practice_user.practice_id", "=", "practices.id")
                                ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                                ->where('users.group_id', '=', Group::PRACTICE_MANAGER)
                                ->where(function ($query1) {
                                    $query1->whereNotNull('users.deleted_at')
                                        ->orWhere('users.deleted_at', '!=', '');
                                })
                                ->distinct()
                        )
                        ->onlyTrashed()
                        ->orderBy($options['field_names'][$options['sort'] - 1], $options['order'] == 1 ? 'asc' : 'desc')
                        ->distinct();
            }
        });


        $data['table'] = View::make('users/_trashedUsers')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('users/index_restore')->with($data);
    }

    public function getRestore($id)
    {
        $result = User::getRestore($id);
        return $result;
    }

    public function updateUsersPassword()
    {
        try {
            set_time_limit(0);
            $users = User::all();
            foreach ($users as $user) {
                $user->setPasswordText($user->password_text);
                $input = [];
                $input['password_text'] = $user->password_text;
                $update_user_password = User::where('id', '=', $user->id)->update($input);
                
            }

            $physicians = Physician::all();
            foreach ($physicians as $physician) {
                $physician->setPasswordText($physician->password_text);
                $input = [];
                $input['password_text'] = $physician->password_text;
                $update_physician_password = Physician::where('id', '=', $physician->id)->update($input);
               
            }

            $data = (object)[];
            $message = "Users and Physicians password is successfully updated";
            $result = array('success' => true, 'data' => $data, 'message' => $message, "status" => 200);
            return response()->json($result);

        } catch (\Illuminate\Database\QueryException $ex) {
            $data = (object)[];
            $message = "Something went wrong.";
            $result = array('success' => false, 'data' => $data, 'message' => $message, "status" => 400);
            return response()->json($result);
        }
    }

}
