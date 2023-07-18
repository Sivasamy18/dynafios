<?php

namespace App\Http\Controllers;

use App\Agreement;
use App\AdminReport;
use App\Console\Commands\AdminReportCommand;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\admin_report_path;

class ReportsController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;

    public function getIndex()
    {
        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('AdminReport', $options);
        $data['table'] = View::make('reports/_reports')->with($data)->render();
        $data['agreements'] = Agreement::getActiveAgreementData();
        $data['report_id'] = Session::get('report_id');
        $data['panel_body'] = View::make('layouts/_report_all_agreements')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('reports/index')->with($data);
    }

    public function getIndexForAllAgreements()
    {

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('AdminReport', $options);
        $data['table'] = View::make('reports/_reports')->with($data)->render();
        $data['agreements'] = Agreement::getAllAgreementData();
        $data['report_id'] = Session::get('report_id');
        $data['panel_body'] = View::make('layouts/_report_all_agreements')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('reports/index')->with($data);
    }

    public function postIndex()
    {
        $agreement_ids = Request::input('agreements');
        $months = [];

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_end_month");
        }

        $agreement_ids = implode(',', $agreement_ids);
        $months = implode(',', $months);

        Artisan::call('reports:admin', [
            'agreements' => $agreement_ids,
            'months' => $months
        ]);

        if (!AdminReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(AdminReportCommand::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(AdminReportCommand::$message),
            'report_id' => AdminReportCommand::$report_id
        ]);
    }

    public function getReport($id)
    {
        $report = AdminReport::findOrFail($id);

        $filename = admin_report_path($report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDelete($id)
    {
        $report = AdminReport::findOrFail($id);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('reports.delete_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('reports.delete_success')
        ]);
    }
}
