<?php

namespace App\Http\Controllers;

use App\Hospital;
use App\ContractType;
use App\Agreement;
use App\PhysicianLog;
use App\Console\Commands\BreakdownReportCommandMultipleMonths;
use App\Console\Commands\PaymentSummaryReportCommandMultipleMonths;
use App\Console\Commands\BreakdownReportCommand;
use App\Practice;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\is_hospital_owner;
use function App\Start\is_practice_manager;

class BreakdownController extends ResourceController
{
    public function getIndex($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);

        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospitalId, 1);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);
        $contract_type_change = Request::input("contract_type_change", null);
        $contractType = Request::input("contract_type", -1);
        $show_deleted_physicians_flag = Request::input("show_deleted_physicians");

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        if ($show_deleted_physicians_flag == 1) {
            $show_deleted_physicians = true;
        } else {
            $show_deleted_physicians = false;
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('HospitalReport', $options, function ($query, $options) use ($hospital) {
            return $query->where('hospital_id', '=', $hospital->id)
                ->where("type", "=", 2);
        });

        $contract_type = ContractType::getHospitalOptions($hospital->id);
        $default_contract_key = key($contract_type);

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/breakdowns/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_type;
        $data["physicians"] = $this->getPhysicianData($hospital, $agreements, $contractType, $start_date, $end_date, $show_deleted_physicians);
        $data['agreements'] = Agreement::getHospitalAgreementDataForReports($hospital->id, $data["contract_type"]);
        $data['showDeletedPhysicianCheckbox'] = true;
        $data['isPhysiciansShowChecked'] = $show_deleted_physicians;
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


        //Allow all users to select multiple months for log report
        $data['form'] = View::make('layouts/_reports_form_multiple_months')->with($data)->render();

        /*if(is_super_user()) {
            $data['form'] = View::make('layouts/_reports_form_multiple_months')->with($data)->render();
        }
        else{
            $data['form'] = View::make('layouts/_reports_form')->with($data)->render();
        }*/

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/breakdowns/index')->with($data);
    }

    private function getPhysicianData($hospital, $agreements, $contract_type = -1, $start_date, $end_date, $show_deleted_physicians = false, $payment_status_report_flag = false)
    {
        if ($agreements == null) {
            return [];
        }

        $query = DB::table("physicians")->select(
        //DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("physicians.first_name as first_name"),
            DB::raw("physicians.last_name as last_name")
        )
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            //->join("practices", "practices.id", "=", "physicians.practice_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            //->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->where("hospitals.id", "=", $hospital->id)
            ->whereIn("contracts.agreement_id", $agreements)
            ->whereNull("contracts.deleted_at")
            //->orderBy("practices.name", "asc")
            ->orderBy("physicians.last_name", "asc")
            ->orderBy("physicians.first_name", "asc");

        if ($contract_type != -1) {
            $query->where("contracts.contract_type_id", "=", $contract_type);
        }

        if (!$show_deleted_physicians) {
            $query->where(function ($query1) {
                $query1->whereNull("physicians.deleted_at");
                //Below condition is commented by akash earlier it was there and working,
                //but in version 8 showing error of invalid timestamp thats why commented.

                // ->orWhere("physicians.deleted_at", "=", "");
            });/*added for soft delete*/
        }

        $results = $query->get();

        $data = [];
        foreach ($results as $result) {
            $currentPractices = DB::table("practices")->select(
                DB::raw("practices.id"),
                DB::raw("practices.name")
            )
                /*->join("physicians","practices.id", "=", "physicians.practice_id")
                ->where("physicians.id", $result->physician_id)*/
                //physician to multiple hospital by 1254 : added to display current practice physicians
                //before this existing physician was displaying previous its hospital  practice
                ->join("physician_practices", "practices.id", "=", "physician_practices.practice_id")
                ->where("physician_practices.physician_id", $result->physician_id)
                ->where("physician_practices.hospital_id", "=", $hospital->id)
                ->get();


            foreach ($currentPractices as $currentPractice) {
                $practice_name = $currentPractice->name;
            }
            $checkContractquery = DB::table("contracts")->select(
                DB::raw("id"),
                DB::raw("agreement_id"),
                DB::raw("created_at"),
                DB::raw("end_date"),
                DB::raw("manual_contract_end_date")
            )
                ->whereIn("agreement_id", $agreements)
                ->where("physician_id", $result->physician_id)
                ->where("end_date", '!=', "0000-00-00 00:00:00")
                ->where("manual_contract_end_date", '!=', "0000-00-00")
                ->get();

            if (count($checkContractquery) > 0) {
                $checkAllContractquery = DB::table("contracts")->select(
                    DB::raw("id"),
                    DB::raw("agreement_id"),
                    DB::raw("created_at"),
                    DB::raw("end_date"),
                    DB::raw("manual_contract_end_date")
                )
                    ->whereIn("agreement_id", $agreements)
                    ->where("physician_id", $result->physician_id)
                    ->get();
                foreach ($checkAllContractquery as $contract) {
                    if ($contract->end_date != "0000-00-00 00:00:00" || $contract->manual_contract_end_date != "0000-00-00") {
                        if($payment_status_report_flag){
                            $start = $start_date;
                            $end = $end_date;
                        }else{
                            $start = explode(": ", $start_date[$contract->agreement_id]);
                            $end = explode(": ", $end_date[$contract->agreement_id]);
                        }
                        if ($contract->end_date != "0000-00-00 00:00:00") {
                            if ($contract->manual_contract_end_date != "0000-00-00") {
                                if (strtotime($contract->manual_contract_end_date) < strtotime($contract->end_date)) {
                                    $contractEndDate = explode(" ", $contract->manual_contract_end_date);
                                } else {
                                    $contractEndDate = explode(" ", $contract->end_date);
                                }
                            } else {
                                $contractEndDate = explode(" ", $contract->end_date);
                            }
                        } else {
                            $contractEndDate = explode(" ", $contract->manual_contract_end_date);
                        }
                        $contractDateEnd = date('Y-m-d', strtotime($contractEndDate[0]));;
                        //echo $paymentDate; // echos today!
                        if($payment_status_report_flag){
                            $reportDateBegin = date('Y-m-d', strtotime($start));
                            $reportDateEnd = date('Y-m-d', strtotime($end));
                        }else{
                            $reportDateBegin = date('Y-m-d', strtotime($start[1]));
                            $reportDateEnd = date('Y-m-d', strtotime($end[1]));
                        }

                        if (($reportDateBegin <= $contractDateEnd) && ($reportDateEnd <= $contractDateEnd)) {
                            if ($contract->end_date != "0000-00-00 00:00:00") {
                                $oldPractices = DB::table("practices")->select(
                                    DB::raw("practices.id"),
                                    DB::raw("practices.name")
                                )
                                    ->join("physician_practice_history", "practices.id", "=", "physician_practice_history.practice_id")
                                    ->where("physician_practice_history.physician_id", $result->physician_id)
                                    ->where("physician_practice_history.created_at", '=', $contract->end_date)
                                    ->get();

                                foreach ($oldPractices as $oldPractice) {
                                    $practice_name = $oldPractice->name;
                                }
                                //$data[$oldPractice->name][$result->physician_id] = "{$result->last_name}, {$result->first_name}";
                            }
                            $data[$practice_name][$result->physician_id] = "{$result->last_name}, {$result->first_name}";
                        }
                    } else {
                        $data[$practice_name][$result->physician_id] = "{$result->last_name}, {$result->first_name}";
                    }
                }
            } else {
                $data[$practice_name][$result->physician_id] = "{$result->last_name}, {$result->first_name}";
            }

        }
        return $data;
    }

    public function postIndex($hospitalId)
    {

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


        $hospital = Hospital::findOrFail($hospitalId);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');
        $months = [];
        $months_start = [];
        $months_end = [];

        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { // Old condition
        if ($agreement_ids == null || $physician_ids == null) { // Added by akash
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months_split[] = Request::input("start_{$agreement_id}_start_month");
            $months_split[] = Request::input("end_{$agreement_id}_start_month");
            $months_start[] = Request::input("start_{$agreement_id}_start_month");
            $months_end[] = Request::input("end_{$agreement_id}_start_month");
        }
        $physician_logs = new PhysicianLog();
        // log::info("post index local time zone",array($localtimeZone));
        $data = $physician_logs->logReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, Request::input('contract_type'), $localtimeZone);
        // log::info("physician log data",array($data));

        $agreement_ids = implode(',', $agreement_ids);
        $physician_ids = implode(',', $physician_ids);
        $months = implode(',', $months);
        $months_split = implode(',', $months_split);

        //Allow all users to select multiple months for log report
        Artisan::call('reports:breakdownmultiplemonths', [
            'hospital' => $hospital->id,
            'contract_type' => Request::input('contract_type'),
            'physicians' => $physician_ids,
            'agreements' => $agreement_ids,
            'months' => $months_split,
            "report_data" => $data
        ]);


        if (!BreakdownReportCommandMultipleMonths::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(BreakdownReportCommand::$message)
            ]);
        }

        /*if(is_super_user()) {
            Artisan::call('reports:breakdownmultiplemonths', [
                'hospital' => $hospital->id,
                'contract_type' => Request::input('contract_type'),
                'physicians' => $physician_ids,
                'agreements' => $agreement_ids,
                'months' => $months_split
            ]);

            if (!BreakdownReportCommandMultipleMonths::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(BreakdownReportCommand::$message)
                ]);
            }
        }
        else{
            Artisan::call('reports:breakdown', [
                'hospital' => $hospital->id,
                'contract_type' => Request::input('contract_type'),
                'physicians' => $physician_ids,
                'agreements' => $agreement_ids,
                'months' => $months
            ]);

            if (!BreakdownReportCommand::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(BreakdownReportCommand::$message)
                ]);
            }
        }*/

        return Redirect::back()->with([
            'success' => Lang::get(BreakdownReportCommand::$message),
            'report_id' => BreakdownReportCommand::$report_id,
            'report_filename' => BreakdownReportCommand::$report_filename
        ]);
    }

    public function getPracticeManagerReport($practice_id, $hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);
        $practice = Practice::findOrFail($practice_id);

        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);
        $contract_type_change = Request::input("contract_type_change", null);
        $contractType = Request::input("contract_type", -1);

        if (!is_practice_manager()) {
            App::abort(403);
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('PracticeManagerReport', $options, function ($query, $options) use ($practice) {
            return $query->where('practice_id', '=', $practice->id)
                ->where("type", "=", 2);
        });

        $contract_type = ContractType::getPracticeOptions($practice->id);
        $default_contract_key = key($contract_type);

        $data['hospital'] = $hospital;
        $data['practice'] = $practice;
        $data['table'] = View::make('practices/_breakdowns')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_type;
        $data["physicians"] = $this->getPhysicianData($hospital, $agreements, $contractType, $start_date, $end_date);
        //$data['agreements'] = Agreement::getHospitalAgreementDataForReports($hospital->id, $data["contract_type"]);
        $data['agreements'] = Agreement::getPracticeAgreementData($practice->id, $data["contract_type"]);
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


        //Allow all users to select multiple months for log report
        $data['form'] = View::make('layouts/_reports_form_multiple_months')->with($data)->render();

        /*if(is_super_user()) {
            $data['form'] = View::make('layouts/_reports_form_multiple_months')->with($data)->render();
        }
        else{
            $data['form'] = View::make('layouts/_reports_form')->with($data)->render();
        }*/

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/breakdown')->with($data);
    }

    public function postPracticeManagerReport($practice_id, $hospitalId)
    {
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

        $hospital = Hospital::findOrFail($hospitalId);
        $practice = Practice::findOrFail($practice_id);

        if (!is_practice_manager()) {
            App::abort(403);
        }

        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');
        $months = [];
        $months_start = [];
        $months_end = [];

        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { // Commented by akash
        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months_split[] = Request::input("start_{$agreement_id}_start_month");
            $months_split[] = Request::input("end_{$agreement_id}_start_month");
            $months_start[] = Request::input("start_{$agreement_id}_start_month");
            $months_end[] = Request::input("end_{$agreement_id}_start_month");
        }
        $physician_logs = new PhysicianLog();

        $data = $physician_logs->logReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, Request::input('contract_type'), $localtimeZone);

        $agreement_ids = implode(',', $agreement_ids);
        $physician_ids = implode(',', $physician_ids);
        $months = implode(',', $months);
        $months_split = implode(',', $months_split);

        //Allow all users to select multiple months for log report
        Artisan::call('reports:breakdownmultiplemonths', [
            'hospital' => $hospital->id,
            'contract_type' => Request::input('contract_type'),
            'physicians' => $physician_ids,
            'agreements' => $agreement_ids,
            'months' => $months_split,
            "report_data" => $data
        ]);

        if (!BreakdownReportCommandMultipleMonths::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(BreakdownReportCommand::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(BreakdownReportCommand::$message),
            'report_id' => BreakdownReportCommand::$report_id,
            'report_filename' => BreakdownReportCommand::$report_filename
        ]);
    }

    // This function is used to get all payment summary report      Rohit Added on 15/09/2022
    public function getPaymentSummaryReport($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);
        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospitalId, 1);
        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);
        // $contract_type_change  = Request::input("contract_type_change", null);
        $contractType = Request::input("contract_type", -1);
        $show_deleted_physicians_flag = Request::input("show_deleted_physicians");

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        if ($show_deleted_physicians_flag == 1) {
            $show_deleted_physicians = true;
        } else {
            $show_deleted_physicians = false;
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('HospitalReport', $options, function ($query, $options) use ($hospital) {
            return $query->where('hospital_id', '=', $hospital->id)
                ->where("type", "=", 6);
        });

        $contract_type = ContractType::getHospitalOptions($hospital->id, true);
        $default_contract_key = key($contract_type);

        $data['hospital'] = $hospital;
        $data['start_date'] = $start_date == null ? date('m-d-Y', strtotime(now())) : $start_date;
        $data['end_date'] = $end_date == null ? date('m-d-Y', strtotime(now())) : $end_date;
        $data['table'] = View::make('hospitals/paymentSummary/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_type;
        $data['agreements'] = [];
        $data["physicians"] = [];

        /*if( $start_date != null){
            $data["physicians"] = $this->getPhysicianData($hospital, $agreements, $contractType,$start_date,$end_date, $show_deleted_physicians);
            $data['agreements'] = Agreement::getHospitalAgreementDataForReportsBasedOnDate($hospital->id, $data["contract_type"],0,$start_date,$end_date);
        }
        else{
            $data["physicians"] = [];
            $data['agreements'] = [];
        }*/
        $data['showDeletedPhysicianCheckbox'] = true;
        $data['isPhysiciansShowChecked'] = $show_deleted_physicians;
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;

        // $data['report_form'] = View::make('layouts/_common_reports_form')->with($data)->render();
        //Allow all users to select multiple months for log report
        $data['form'] = View::make('layouts/_reports_form_payment_summary')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/paymentSummary/index')->with($data);
    }

    // This function is used to genarate payment summary report      Rohit Added on 15/09/2022
    public function postPaymentSummaryReport($hospitalId)
    {
        set_time_limit(0);
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

        $hospital = Hospital::findOrFail($hospitalId);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');
        $start_date = Request::input('start_date');
        $end_date = Request::input('end_date');

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        $physician_logs = new PhysicianLog();
        $data = $physician_logs->paymentSummarylogReportData($hospital->id, $agreement_ids, $physician_ids, $start_date, $end_date, Request::input('contract_type'), $localtimeZone);
        $agreement_ids = implode(',', $agreement_ids);
        $physician_ids = implode(',', $physician_ids);
        //Allow all users to select multiple months for log report
        Artisan::call('reports:paymentsummarymultiplemonths', [
            'hospital' => $hospital->id,
            'contract_type' => Request::input('contract_type'),
            'physicians' => $physician_ids,
            'agreements' => $agreement_ids,
            "report_data" => $data,
            "localtimeZone" => $localtimeZone,
            "period" => format_date($start_date) . " - " . format_date($end_date)
        ]);


        if (!PaymentSummaryReportCommandMultipleMonths::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(BreakdownReportCommand::$message)
            ]);
        }
        return Redirect::back()->with([
            'success' => Lang::get(BreakdownReportCommand::$message),
            'report_id' => BreakdownReportCommand::$report_id,
            'report_filename' => BreakdownReportCommand::$report_filename
        ]);
    }

    public function getAgreements()
    {
        $request = $_GET;
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];
        $contract_type = $request['contract_type'];
        $hospital_id = $request['hospital_id'];
        $agreement = $request['agreement'];
        if ($agreement > 0) {
            $agreements = $request['agreements'];
        }
        $show_deleted_physicians = false;
        $hospital = Hospital::findOrFail($hospital_id);
        if (Request::ajax()) {
            $data['agreements'] = Agreement::getHospitalAgreementDataForReportsBasedOnDate($hospital_id, $contract_type, 0, $start_date, $end_date);
            if ($agreement > 0 && count($agreements) > 0) {
                $data["physicians"] = $this->getPhysicianData($hospital, $agreements, $contract_type, $start_date, $end_date, $show_deleted_physicians, true);
            }
            return $data;
        }
    }
}
