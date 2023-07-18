<?php

namespace App\Http\Controllers;

use App\SystemLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class SystemLogsController extends BaseController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;

    public function getIndex()
    {
        $options = [
            "sort" => Request::input("sort", 3),
            "order" => Request::input("order", 2),
            "sort_min" => 1,
            "sort_max" => 3,
            "appends" => ["sort", "order"],
            "field_names" => ["user_id", "url", "created_at"]
        ];

        $data = $this->queryModel("SystemLog", $options);
        $data['table'] = View::make("system_logs/_system_logs")->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make("system_logs/index")->with($data);
    }

    public function getShow($id)
    {
        $system_log = SystemLog::findOrFail($id);
        return View::make('system_logs/show')->with([
            "system_log" => $system_log
        ]);
    }

    public function getDelete($id)
    {
    }
}
