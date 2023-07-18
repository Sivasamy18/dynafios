<?php

namespace App\Http\Controllers;

use App\Attestation;
use App\AttestationQuestion;
use App\State;
use App\Physician;
use App\Contract;
use App\Practice;
use App\PhysicianPractices;
use App\PhysicianAttestationAnswer;
use App\Agreement;
use App\Hospital;
use App\ContractType;
use App\customClasses\PaymentFrequencyFactoryClass;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade as PDF;
use App\AttestationReport;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\is_hospital_admin;
use function App\Start\is_physician;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\attestation_report_path;

class AttestationsController extends ResourceController
{
    protected $requireAuth = true;

    public function getAttestation()
    {
        if (!is_super_user())
            App::abort(403);

        $data = Attestation::getAttestation();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('attestations/index')->with($data);
    }

    public function getCreate()
    {
        if (!is_super_user())
            App::abort(403);

        $attestation = Request::input('attestation', 0);

        $states = options(State::orderBy('name')->get(), 'id', 'name');
        $attestations = options(Attestation::where('is_active', '=', true)->get(), 'id', 'name');
        if ($attestation > 0 && $attestation == 5) {
            $question_types = options(DB::table("attestation_question_types")->where('is_active', '=', true)->get(), 'id', 'type');
        } else {
            $question_types = options(DB::table("attestation_question_types")->where('is_active', '=', true)->where('id', '!=', 3)->get(), 'id', 'type');
        }
        $attestation_types = options(DB::table('attestation_types')->where('is_active', '=', true)->get(), 'id', 'name');

        $data = [
            'states' => $states,
            'attestations' => $attestations,
            'question_types' => $question_types,
            'attestation_types' => $attestation_types
        ];

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('attestations/create')->with($data);
    }

    public function postCreate()
    {
        if (!is_super_user())
            App::abort(403);

        $result = AttestationQuestion::createAttestationQuestion();
        return $result;
    }

    public function getEdit($state_id, $attestation_id, $question_id)
    {
        if (!is_super_user())
            App::abort(403);

        $data = AttestationQuestion::getEditAttestationQuestion($state_id, $attestation_id, $question_id);

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('attestations/question_edit')->with($data);
    }

    public function postEdit()
    {
        if (!is_super_user())
            App::abort(403);

        $state = Request::input('state');
        $attestation = Request::input('attestation');
        $question_id = Request::input('question_id');
        $question_type = Request::input('question_type');
        $question = $_POST['editor'];
        $question = htmlentities($question, ENT_HTML5, 'UTF-8');
        $attestation_type = Request::input('attestation_type');

        $check_exist = AttestationQuestion::select('*')
            ->where('state_id', '=', $state)
            ->where('attestation_type', '=', $attestation_type)
            ->where('attestation_id', '=', $attestation)
            ->where('question', '=', $question)
            ->where('id', '!=', $question_id)
            ->count();

        if ($check_exist > 0) {
            return Redirect::route('attestations.create')->with([
                'error' => Lang::get('attestations.attestation_exist_error')
            ]);
        }

        $result = AttestationQuestion::updateAttestationQuestion($state, $attestation, $question_id, $question, $question_type, $attestation_type);

        return Redirect::route('attestations.index')->with([
            'success' => Lang::get('attestations.edit_success')
        ]);
    }

    public function getDeleteAttestationQuestion($state_id, $attestation_id, $question_id)
    {
        if (!is_super_user())
            App::abort(403);

        $state = State::findOrFail($state_id);
        $attestation_question = AttestationQuestion::select('*')
            ->where('state_id', '=', $state_id)
            ->where('attestation_id', '=', $attestation_id)
            ->where('id', '=', $question_id)
            ->where('is_active', '=', true)
            ->first();

        if (!$attestation_question->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('attestations.delete_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('attestations.delete_success')
        ]);
    }

    public function getPhysicianAttestation($physician_id, $contract_id, $date_selector)
    {
        $data['physician'] = Physician::FindOrFail($physician_id);
        $data['contract'] = Contract::FindOrFail($contract_id);
        $data['dateSelector'] = $date_selector;

        $contract = Contract::findOrFail($contract_id);
        $approve_logs = AttestationQuestion::getApproveLogs($contract);
        $date_selectors = [];
        $renumbered = [];
        $dates_range_arr = [];

        if ($date_selector == "All") {
            if (count($approve_logs) > 0) {
                foreach ($approve_logs as $log) {
                    array_push($date_selectors, $log['date_selector']);
                }
                $date_selectors = array_unique($date_selectors);
                $dates_range_arr = array_reverse($date_selectors);
                $renumbered = array_merge($dates_range_arr, array());
                json_encode($renumbered);
            }
        } else {
            $renumbered[] = $date_selector;
        }

        $agreement = Agreement::select('agreements.*')
            ->where('agreements.id', '=', $contract->agreement_id)
            ->first();
        $agreement_start_date = $agreement->start_date;
        $agreement_month_end_date = date("Y-m-t", strtotime($agreement->start_date));
        $temp_range_start_date = '';
        $check_annually = true;
        $check_monthly = true;
        $log_date = '00-0000';

        foreach ($renumbered as $date_range) {
            $temp_date_selector_arr = explode(" - ", $date_range);
            $temp_range_start_date = str_replace("-", "/", $temp_date_selector_arr[0]);

            if ($log_date != date('m-Y', strtotime($temp_range_start_date))) {
                $log_date = date('m-Y', strtotime($temp_range_start_date));

                // Annually
                if ($contract->state_attestations_annually) {
                    if ((date('Y-m-d', strtotime($temp_range_start_date)) >= $agreement_start_date && date('Y-m-d', strtotime($temp_range_start_date)) <= $agreement_month_end_date) || (date('m', strtotime($temp_range_start_date)) == 1)) {
                        $check_exist_annually = DB::table('attestation_questions_answer_header')->select('*')
                            ->where('physician_id', '=', $physician_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('attestation_type', '=', 2)
                            ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($temp_range_start_date)), date('Y-m-t', strtotime($temp_range_start_date))])
                            ->get();

                        if (count($check_exist_annually) == 0) {
                            $check_annually = false;
                            break;
                        }
                    }
                }

                // Monthly
                if ($contract->state_attestations_monthly) {
                    $check_exist_monthly = DB::table('attestation_questions_answer_header')->select('*')
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('attestation_type', '=', 1)
                        ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($temp_range_start_date)), date('Y-m-t', strtotime($temp_range_start_date))])
                        ->get();

                    if (count($check_exist_monthly) == 0) {
                        $check_monthly = false;
                    }
                }
            }
        }

        if (!$check_annually && $contract->state_attestations_annually) {
            $data['attestations'] = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 2, $physician_id);
            return View::make('attestations/physician_attestation')->with($data);
        } else if (!$check_monthly && $contract->state_attestations_monthly) {
            $data['attestations'] = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 1, $physician_id);
            return View::make('attestations/physician_attestation_monthly')->with($data);
        } else {
            return AttestationQuestion::sigantureApprovePage($physician_id, $contract_id, $date_selector);
        }
    }

    public function getPhysicianMonthlyAttestation($physician_id, $contract_id, $date_selector)
    {
        $data['physician'] = Physician::FindOrFail($physician_id);
        $data['contract'] = Contract::FindOrFail($contract_id);
        $data['dateSelector'] = $date_selector;

        $contract = Contract::findOrFail($contract_id);
        $approve_logs = AttestationQuestion::getApproveLogs($contract);
        $date_selectors = [];
        $renumbered = [];
        $dates_range_arr = [];
        // array_push($date_selectors,'All');
        if (count($approve_logs) > 0) {
            foreach ($approve_logs as $log) {
                array_push($date_selectors, $log['date_selector']);
            }
            $date_selectors = array_unique($date_selectors);
            $dates_range_arr = array_reverse($date_selectors);
            $renumbered = array_merge($dates_range_arr, array());
            json_encode($renumbered);
        }

        $agreement = Agreement::select('agreements.*')
            ->where('agreements.id', '=', $contract->agreement_id)
            ->first();
        $agreement_start_date = $agreement->start_date;
        $agreement_month_end_date = date("Y-m-t", strtotime($agreement->start_date));
        $temp_range_start_date = '';
        $check_monthly = true;
        $log_date = '00-0000';

        foreach ($renumbered as $date_range) {
            $temp_date_selector_arr = explode(" - ", $date_range);
            $temp_range_start_date = str_replace("-", "/", $temp_date_selector_arr[0]);

            if ($log_date != date('m-Y', strtotime($temp_range_start_date))) {
                $log_date = date('m-Y', strtotime($temp_range_start_date));

                if ($contract->state_attestations_monthly) {
                    $check_exist_monthly = DB::table('attestation_questions_answer_header')->select('*')
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('attestation_type', '=', 1)
                        ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($temp_range_start_date)), date('Y-m-t', strtotime($temp_range_start_date))])
                        ->get();

                    if (count($check_exist_monthly) == 0) {
                        $check_monthly = false;
                    }
                }
            }
        }

        if (!$check_monthly) {
            $data['attestations'] = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 1, $physician_id);
            return View::make('attestations/physician_attestation_monthly')->with($data);
        } else {
            return AttestationQuestion::sigantureApprovePage($physician_id, $contract_id, $date_selector);
        }
    }

    public function getMonthlyPhysicianAttestations($contract_id)
    {
        if (Request::ajax()) {
            if (!is_physician())
                App::abort(403);

            $physician_id = Physician::select('id')->where('email', '=', Auth::user()->email)->first();
            $result = AttestationQuestion::getAnnuallyMonthlyAttestations($contract_id, 1, $physician_id->id);
            return $result;
        }
    }

    public function getReports($hospital_id)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_admin())
            App::abort(403);

        $hospital = Hospital::findOrFail($hospital_id);
        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospital_id, 1);
        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);
        $contract_type_change = Request::input("contract_type_change", null);
        $contractType = Request::input("contract_type", -1);
        $attestation_type = Request::input("attestation_type", null);

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('AttestationReport', $options, function ($query, $options) use ($hospital) {
            return $query->where("hospital_id", "=", $hospital->id);
        });

        $contract_type = options(ContractType::where('id', '=', 20)->get(), 'id', 'name');  // ContractType::getHospitalOptions($hospital->id);
        $default_contract_key = key($contract_type);

        $attestation_types = options(DB::table('attestation_types')->where('is_active', '=', true)->get(), 'id', 'name');
        if ($attestation_type != null) {
            $default_attestation_type_key = $attestation_type;
        } else {
            $default_attestation_type_key = key($attestation_types);
        }


        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/attestation/_reports_table')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_type;
        $data['attestation_type'] = Request::input("attestation_type", $default_attestation_type_key);
        $data['attestation_types'] = $attestation_types;
        $data["physicians"] = Physician::getPhysicianData($hospital, $agreements, $contractType, $start_date, $end_date, false);
        $data['agreements'] = Agreement::getHospitalAgreementDataForAttestationReports($hospital->id, $data["contract_type"], false);
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;

        if ($agreements != null && $contract_type_change == 0) {
            foreach ($agreements as $agreement) {
                if ($start_date[$agreement] != null && $end_date[$agreement] != null) {
                    $start = explode(": ", $start_date[$agreement]);
                    $end = explode(": ", $end_date[$agreement]);
                    $data['selected_start_date'][$agreement] = $start[0];
                    $data['selected_end_date'][$agreement] = $end[0];
                } else {
                    $data['selected_start_date'][$agreement] = $start_date[$agreement];
                    $data['selected_end_date'][$agreement] = $end_date[$agreement];
                }
            }
        }

        $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        $data['form'] = View::make('layouts/_attestation_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/attestation/index')->with($data);
    }

    public function postReports($hospital_id)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_admin())
            App::abort(403);

        $hospital = Hospital::findOrFail($hospital_id);
        $attestation_type = Request::input('attestation_type');
        $contract_type = Request::input('contract_type');
        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        $timeZone = str_replace(' ', '_', $localtimeZone);
        $timeZone = str_replace('/', '', $timeZone);
        $timeZone = str_replace(':', '', $timeZone);

        $data['results'] = AttestationQuestion::attestationReportData($hospital, $agreement_ids, $physician_ids, $contract_type, $attestation_type);

        if ($data['results']) {
            $report_path = attestation_report_path();
            if (!file_exists($report_path)) {
                mkdir($report_path, 0777, true);
            }

            $report_filename = "Attestation_" . $hospital->name . "_" . $timeZone . ".pdf";
            $customPaper = array(0, 0, 1683.78, 595.28);
            $pdf = PDF::loadView('attestations/attestation_pdf', $data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);

            $attestation_report = new AttestationReport();
            $attestation_report->hospital_id = $hospital->id;
            $attestation_report->filename = $report_filename;
            $attestation_report->attestation_type = $attestation_type;
            $attestation_report->created_by = Auth::user()->id;
            $attestation_report->updated_by = Auth::user()->id;
            $attestation_report->save();

            // return $pdf->download($report_filename)

            return Redirect::route('hospitals.attestation', [$hospital->id])->with([
                'success' => Lang::get('attestations.file_create')
            ]);
        } else {
            return Redirect::back()->with([
                'error' => Lang::get('attestations.not_found_error')
            ]);
        }
    }

    public function downloadReport($hospital_id, $report_id)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_admin())
            App::abort(403);

        $hospital = Hospital::findOrFail($hospital_id);
        $report = AttestationReport::findOrFail($report_id);
        $filename = attestation_report_path($report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteReport($hospital_id, $report_id)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_admin())
            App::abort(403);

        $hospital = Hospital::findOrFail($hospital_id);
        $report = AttestationReport::findOrFail($report_id);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('attestations.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('attestations.delete_report_success')
        ]);
    }
}
