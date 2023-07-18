<?php

namespace App\Start;

use App\SystemLog;
use Auth;
use URL;
use Request;

class Dashboard
{
    /**
     * Logs a  user's actions to the database.
     */
    public static function log_action()
    {
        if (Auth::check()) {
            $system_log = new SystemLog();
            $system_log->user_id = Auth::user()->id;
            $system_log->url = URL::current();
            $system_log->input = json_encode(Request::input());
            $system_log->save();
        }
    }
}
