<?php

namespace App\Http\Controllers;

use App\Hospital;
use App\Group;
use App\Agreement;
use App\Console\Commands\CompliancePhysicianReport;
use App\Console\Commands\ComplianceContractTypeReport;
use App\Console\Commands\CompliancePracticeReport;
use App\ComplianceReport;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\PhysicianLog;
use App\ContractType;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\complience_report_path;

class ComplianceController extends ResourceController // Controller
{
    public function getReports($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $hospital_list = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospitals = array();
        foreach ($hospital_list as $hospital_id => $hospital_name) {
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospitals = $hospital;
            }
        }

        $hospital_list = $default + $hospitals;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('ComplianceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 1);
        });

        $data['group'] = $group;
        $data['table'] = View::make('compliance/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);
        $data["contract_types"] = ContractType::getContractTypesForComplianceReports($user_id, $data["facility"]);
        $data["contract_type"] = Request::input("contract_type", 0);
        $data['agreements'] = Agreement::getHospitalAgreementDataForComplianceReports($user_id, $data["facility"], $data["contract_type"]);
        $data['form'] = View::make('layouts/_compliance_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        return View::make('compliance/reports')->with($data);
    }

    public function getComplianceAgreementsByHospital($facility, $contract_type)
    {
        $user_id = Auth::user()->id;

        if (Request::ajax()) {
            $data['agreement'] = Agreement::getHospitalAgreementDataForComplianceReports($user_id, $facility, $contract_type);
            return $data;
        }
    }

    public function getcompliancePracticeReport($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $hospital_list = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospitals = array();
        foreach ($hospital_list as $hospital_id => $hospital_name) {
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospitals = $hospital;
            }
        }

        $hospital_list = $default + $hospitals;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('ComplianceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 2);
        });

        $data['group'] = $group;
        $data['table'] = View::make('compliance/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);
        $data["contract_types"] = ContractType::getContractTypesForComplianceReports($user_id, $data["facility"]);
        $data["contract_type"] = Request::input("contract_type", 0);
        $data['agreements'] = Agreement::getHospitalAgreementDataForComplianceReports($user_id, $data["facility"], $data["contract_type"]);
        $data['form'] = View::make('layouts/_compliance_practice_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        return View::make('compliance/practice_reports')->with($data);
    }

    public function getcomplianceContractTypeReport($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $hospital_list = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospitals = array();
        foreach ($hospital_list as $hospital_id => $hospital_name) {
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospitals = $hospital;
            }
        }

        $hospital_list = $default + $hospitals;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('ComplianceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 3);
        });

        $data['group'] = $group;
        $data['table'] = View::make('compliance/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", 0);
        $data["contract_types"] = ContractType::getContractTypesForComplianceReports($user_id, $data["facility"]);
        $data["contract_type"] = Request::input("contract_type", 0);
        $data['agreements'] = Agreement::getHospitalAgreementDataForComplianceReports($user_id, $data["facility"], $data["contract_type"]);
        $data['form'] = View::make('layouts/_compliance_contract_type_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        return View::make('compliance/contract_type_reports')->with($data);
    }

    public function postPhysiciancomplianceReport($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $agreement_ids = Request::input("agreements");
        $contract_type = Request::input("contract_type");
        if ($agreement_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }
        $reportdata = PhysicianLog::fetch_log_rejection($user_id, $agreement_ids, $contract_type);
        // Log::info('reportdata',array($reportdata));
        //Allow all users to select multiple months for log report
        Artisan::call('reports:compliancePhysicianReport', [
            'agreements' => $agreement_ids,
            "report_data" => $reportdata
        ]);

        if (!CompliancePhysicianReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(CompliancePhysicianReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(CompliancePhysicianReport::$message),
            'report_id' => CompliancePhysicianReport::$report_id,
            'report_filename' => CompliancePhysicianReport::$report_filename
        ]);
    }

    public function downloadReport($report_id)
    {
        //Log::info("getReport start");
        $report = ComplianceReport::findOrFail($report_id);

        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $filename = complience_report_path($report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
        //Log::info("getReport end");
    }


    public function postPracticeComplianceReport($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $agreement_ids = Request::input("agreements");
        $contract_type = Request::input("contract_type");
        if ($agreement_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }
        $reportdata = PhysicianLog::fetch_log_rejection_practice($user_id, $agreement_ids, $contract_type);
        // Log::info('reportdata',array($reportdata));
        //Allow all users to select multiple months for log report
        Artisan::call('reports:compliancePracticeReport', [
            'agreements' => $agreement_ids,
            "report_data" => $reportdata
        ]);

        if (!CompliancePracticeReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(CompliancePracticeReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(CompliancePracticeReport::$message),
            'report_id' => CompliancePracticeReport::$report_id,
            'report_filename' => CompliancePracticeReport::$report_filename
        ]);
    }

    public function postContractTypeComplianceReport($group)
    {
        if (!($group == Group::HOSPITAL_ADMIN) && !($group == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $agreement_ids = Request::input("agreements");
        $contract_type = Request::input("contract_type");
        if ($agreement_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }
        $reportdata = PhysicianLog::fetch_log_rejection_contract_type($user_id, $agreement_ids, $contract_type);
        // Log::info('reportdata',array($reportdata));
        //Allow all users to select multiple months for log report
        Artisan::call('reports:complianceContractTypeReport', [
            'agreements' => $agreement_ids,
            "report_data" => $reportdata
        ]);

        if (!ComplianceContractTypeReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(ComplianceContractTypeReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(ComplianceContractTypeReport::$message),
            'report_id' => ComplianceContractTypeReport::$report_id,
            'report_filename' => ComplianceContractTypeReport::$report_filename
        ]);
    }

    public function getDeleteReport($report_id)
    {
        $report = ComplianceReport::findOrFail($report_id);

        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('hospital.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('hospitals.delete_report_success')
        ]);
    }

    public function getContractTypesForComplianceReport($facility)
    {
        $user_id = Auth::user()->id;
        if (Request::ajax()) {
            $data['contract_types'] = ContractType::getContractTypesForComplianceReports($user_id, $facility);
            return $data;
        }
    }
}

?>