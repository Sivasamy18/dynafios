<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class UserSwitchController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;
    protected $requireSuperUserOptions = [
        'except' => ['restoreUser']
    ];

    public function switchUser()
    {
        Session::put('existing_user_id', Auth::user()->id);
        Session::put('user_is_switched', true);
        $newuserId = Request::input('new_user_id');
        Auth::loginUsingId($newuserId);
        return Redirect::route('dashboard.index');
    }

    public function restoreUser()
    {
        $oldUserId = Session::get('existing_user_id');
        Auth::loginUsingId($oldUserId);
        Session::forget('existing_user_id');
        Session::forget('user_is_switched');
        return Redirect::route('dashboard.index');
    }
}
