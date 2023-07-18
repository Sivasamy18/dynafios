<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Console\Commands\ApprovalStatusReport;
use App\LogApproval;
use App\PhysicianLog;
use App\Hospital;
use App\Agreement;
use App\Practice;
use App\Physician;
use App\Contract;
use App\ContractType;
use App\PaymentType;
use App\Group;
use App\ColumnPreferencesApprovalDashboard;
use App\ContractName;
use App\ApprovalManagerInfo;
use App\ContractRate;
use App\PerformanceReport;
use App\Specialty;
use App\HealthSystemUsers;
use App\HealthSystemRegion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\PerformancePhysicianReport;
use App\Console\Commands\PerformanceApproverReport;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\is_hospital_admin;
use function App\Start\is_hospital_cfo;
use function App\Start\is_hospital_user_healthSystem_user;
use function App\Start\is_super_hospital_user;
use Illuminate\Support\Facades\Response as FacadeResponse;
use function App\Start\performance_report_path;

class PerformanceController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function getPerformance()
    {
        if (!is_super_hospital_user() && !is_hospital_cfo() && !is_hospital_admin()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                ]);
            }
        }

        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;

        $default_selected_manager = 0;
        $data['hospitals'] = Hospital::getHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));

        next($data['hospitals']);
        $default_selected_hospital = key($data['hospitals']);

        $data['agreements'] = Agreement::getAgreements($this->currentUser->id, $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = array(key($data['agreements']));
        // Log::info("selected agreement for practice func",array(Request::input('agreement')));

        $data['practices'] = Practice::getPractice($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = array(key($data['practices']));
        // Log::info("default_practice",array($default_practice));

        $data['physicians'] = Physician::getPhysician($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice));
        $default_physician = array(key($data['physicians']));
        // Log::info("default_physician",array($default_physician));

        $data['payment_types'] = PaymentType::getPaymentTypeForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));

        $data['contract_types'] = ContractType::getContractTypesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians'], $data['payment_types'], Request::input('payment_type', $default_payment_key));
        $default_contract_key = array(key($data['contract_types']));
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));


        $data['contract_names'] = ContractName::getContractNamesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians'], $data['payment_types'], Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), $data['contract_types']);
        $default_contract_name_key = array(key($data['contract_names']));
        $data['contract_name'] = Request::input('contract_name', Request::input('contract_name', $default_contract_name_key));
        // Log::info("default_contract_name_key",array($default_contract_name_key));

        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $levelOne = self::dataForPerformanceDashboard($this->currentUser->id, Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key),
            Request::input('contract_name', $default_contract_name_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        $start_date = Request::input('start_date', $levelOne['dates']['start']);
        $end_date = Request::input('end_date', $levelOne['dates']['end']);

        $dates = $levelOne['dates'];
        if ($dates['start'] != NULL && $dates['end'] != NULL) {
            $data['dates'] = $physicianLog->datesOptions($dates);
        } else {
            $data['dates']['start_dates'][] = "1: " . date("m/d/Y", strtotime("first day of previous month"));
            $data['dates']['end_dates'][] = "1: " . date("m/d/Y", strtotime("last day of previous month"));
        }
        // Log::info("data['dates']",array($data['dates']));
        // if(in_array("1: 01/01/1970",$data['dates']['start_dates']))
        // {  
        //     unset($data['dates']);
        //     $data['dates']['start_dates'][] ="1: ".date("m/d/Y", strtotime("first day of previous month"));
        //     $data['dates']['end_dates'][] ="1: ".date("m/d/Y", strtotime("last day of previous month"));
        //     Log::info("inside first if dates array",array($data['dates']));
        // }

        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['payment_type'] = Request::input('payment_type', $default_payment_key);
        $data['contract_type'] = Request::input('contract_type', $default_contract_key);
        $data['contract_name'] = Request::input('contract_name', $default_contract_name_key);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);

        $data['end_date'] = date("Y-m-t", strtotime(Request::input('end_date', $end_date))); // get the last day of the month to avoid date selection issue and data mismatch issue.

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        /*
            @ajax request for filtering payment status page - starts
        */
        // $data['column_preferences']=new ColumnPreferencesApprovalDashboard();
        // $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id','=',Auth::user()->id)->first();
        if (Request::ajax()) {
            $view = View::make('hospitals/performance/indexAjaxRequest')->with($data)->render();
            // Log::info("data for performance dashboard AJAX call",array($data));
            return Response::json($view);
        }
        $data['report_id'] = Session::get('report_id');
        $hospital_ids = array_keys($data['hospitals']);
        $default_hospital_id = (isset($hospital_ids[1]) ? $hospital_ids[1] : $hospital_ids[0]);
        //$data['assertation_popup_text']=Hospital::get_assertation_text($default_hospital_id);
        //  Log::info("FINAL data for performance dashboard",array($data));
        return View::make('hospitals.performance.index')->with($data);
    }

    public function getTotalHoursForPhysicanLog()
    {
        $request = $_POST;
        //    Log::info("get total hours for physician logs chaitraly request::",array($request));

        //for checking activity and its log by physican ids
        //  $physican_activity_log = DB::table("physician_logs")
        $physican_activity_log = PhysicianLog::
        select(DB::raw("physicians.id as physican_id, actions.name, SUM(physician_logs.duration) as total_duration, action_categories.name as category_name, action_categories.id as category_id"))
            ->join("actions", "actions.id", "=", "physician_logs.action_id")
            ->join("action_categories", "action_categories.id", "=", "actions.category_id")
            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
            ->join("contracts", "contracts.id", "=", "physician_logs.contract_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("payment_types", "payment_types.id", "=", "contracts.payment_type_id")
            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->where("agreements.hospital_id", "=", $request['hospital_id'])
            ->where("physician_logs.date", ">=", $request['start_date'])
            ->where("physician_logs.date", "<=", $request['end_date']);
        //	 Log::info("1st physician log activity",array($physican_activity_log));

        //  if(!empty($request['hospital_id'])){
        // 	if($request['hospital_id'][0]==0){
        // 		unset($request['hospital_id'][0]);
        // 	 }
        // 	if(count($request['hospital_id']) > 0)
        // 		$physican_activity_log = $physican_activity_log->whereIn("hospitals.id",$request['hospital_id']);
        //  }
        if (!empty($request['agreement_id'])) {
            if ($request['agreement_id'][0] == 0) {
                unset($request['agreement_id'][0]);
            }
            if (count($request['agreement_id']) > 0)
                $physican_activity_log = $physican_activity_log->whereIn("agreements.id", $request['agreement_id']);
        }
        if (!empty($request['contract_type'])) {
            if ($request['contract_type'][0] == 0) {
                unset($request['contract_type'][0]);
            }
            if (count($request['contract_type']) > 0)
                $physican_activity_log = $physican_activity_log->whereIn("contract_types.id", $request['contract_type']);
        }
        if (!empty($request['contract_id'])) {
            if ($request['contract_id'][0] == 0) {
                unset($request['contract_id'][0]);
            }
            if (count($request['contract_id']) > 0)
                $physican_activity_log = $physican_activity_log->whereIn("contracts.id", $request['contract_id']);
        }
        if (!empty($request['contract_name'])) {
            if ($request['contract_name'][0] == 0) {
                unset($request['contract_name'][0]);
            }
            if (count($request['contract_name']) > 0)
                $physican_activity_log = $physican_activity_log->whereIn("contract_names.id", $request['contract_name']);
        }
        if (!empty($request['physician_id'])) {
            if ($request['physician_id'][0] == 0) {
                unset($request['physician_id'][0]);
            }
            if (count($request['physician_id']) > 0)
                $physican_activity_log = $physican_activity_log->whereIn("physicians.id", $request['physician_id']);
        }
        $physican_activity_log = $physican_activity_log->groupBy('actions.category_id')
            ->get();


        $data_array = array();
        $all_data = array();

        foreach ($physican_activity_log as $activity_log) {
            $data_array[] = [
                "physican_id" => $activity_log->physican_id,
                "activity_name" => $activity_log->category_name,
                "total_duration" => $activity_log->total_duration,
                "category_id" => $activity_log->category_id
            ];
        }
        if (Request::ajax()) {
            $data['type_data'] = $data_array;
        }
        // Log::info("get total hours for physician logs chaitraly response::",array($data));
        return View::make('hospitals.performance.performance_chart')->with($data)->render();
    }

    public static function dataForPerformanceDashboard($user_id, $payment_type, $contract_type, $contract_name_id, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date, $end_date, $report = false)
    {
        // if(count($physician_id)> 0)
        // {
        //     $physician_ids =$physician_id;
        // }

        // else if(gettype($physician_id) === 'array')
        // {
        //         $physician_ids =$physician_id;
        // }

        $physician_ids = $physician_id;

        $responceResult = array();
        $result = array();

        // $contracts_for_user = Contract::select("id")
        // ->where("id", ">", 0)
        // ->whereIn("physician_id",$physician_ids)
        // ->whereNotIn("payment_type_id",[3,5])
        // ->pluck("id"); //this retrieves contracts as per physician filters only, not any other filter condiiton applied selected matching the where condition

        $dates = self::datesForPerformanceDashboard($user_id, $payment_type, $contract_type, $contract_name_id, $hospital_id, $agreement_id, $practice_id, $physician_id, '', '');
        // Log::info("date()1 dates return from date()2",array($dates));
        if ($start_date == '' && $end_date == '') {
            $start_date = $dates->temp_start_date;
            $end_date = $dates->temp_end_date;
        }

        $responceResult['dates'] = ["start" => $dates->temp_start_date, "end" => $dates->temp_end_date];
        // Log::info("dataa for performance dashboard",$responceResult);
        return $responceResult;

    }

    //Chaitraly::Start and end date for Performance Dashboard
    public static function datesForPerformanceDashboard($user_id, $payment_type, $contract_type, $contract_name_id, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '')
    {
        // Log::info("datesForPerformanceDashboard() request param == ",array($user_id,$contracts_for_user,$payment_type, $contract_type,$contract_name_id,$hospital_id, $agreement_id , $practice_id , $physician_id, $start_date = '', $end_date = ''));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $dates = PhysicianLog::select(
            DB::raw("MIN(physician_logs.date) as temp_start_date"),
            DB::raw("MAX(physician_logs.date) as temp_end_date"))
            ->distinct('physician_logs.id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            // ->where("physician_logs.signature", "=", 0)
            //->where("physician_logs.approval_date", "=", "0000-00-00")
            //->whereNotIn("contracts.payment_type_id",[3,5]) //Chaitraly new added 14/10/21
            // ->where(function ($query) use ($proxy_check_id) {
            //     $query->whereIn("physician_logs.next_approver_user",$proxy_check_id) //added this condition for checking with proxy approvers
            //     //->where("physician_logs.next_approver_user", "=", $user_id)
            //     // ->orWhere("physician_logs.next_approver_user", "=", 0); // Old condition which was used to fetch all the data including unapporved logs to approver leverl
            //     ->Where("physician_logs.next_approver_user", "!=", 0);
            // })
            // ->where(function ($query1) use ($user_id) {
            //     $query1->whereNull("physician_logs.deleted_at");
            //     // ->orWhere("physician_logs.deleted_at", "=", "");
            // })/*added for soft delete*/
            // ->whereIN("physician_logs.contract_id", $contracts_for_user)
            ->where("physician_logs.date", "<=", $prior_month_end_date);
        if ($payment_type != 0) {
            $dates = $dates->where('contracts.payment_type_id', '=', $payment_type);
        }
        // if ($contract_type != 0) {
        //     $dates = $dates->where('contracts.contract_type_id', '=', $contract_type);
        // }
        if ($contract_type != 0) {
            //         $dates = $dates->where('contracts.contract_type_id', '=', $contract_type);
            // }
            // else if(gettype($contract_type) === 'array')
            // {
            $dates = $dates->whereIn('contracts.contract_type_id', $contract_type);
        }
        if ($hospital_id != 0) {
            $dates = $dates->where('agreements.hospital_id', '=', $hospital_id);
        }
        // if ($agreement_id != 0) {
        //     $dates = $dates->where('contracts.agreement_id', '=', $agreement_id);
        // }
        if ($agreement_id != 0) {
            //     $dates = $dates->where('contracts.agreement_id', '=', $agreement_id);
            // }
            // else if(gettype($agreement_id) === 'array')
            // {
            $dates = $dates->whereIn('contracts.agreement_id', [$agreement_id]);
        }
        // if ($practice_id != 0) {
        //     $dates = $dates->where('physician_logs.practice_id', '=', $practice_id);
        // }

        if ($practice_id != 0) {
            //         $dates = $dates->where('physician_logs.practice_id', '=', $practice_id);
            // }
            // else if(gettype($practice_id) === 'array')
            // {
            $dates = $dates->whereIn('physician_logs.practice_id', [$practice_id]);
        }

        // if ($physician_id != 0) {
        //     $dates = $dates->where('contracts.physician_id', '=', $physician_id);
        // }
        if ($physician_id != 0) {
            //     $dates = $dates->where('contracts.physician_id', '=', $physician_id);
            // }
            // else if(gettype($physician_id) === 'array')
            // {
            $dates = $dates->whereIn('contracts.physician_id', [$physician_id]);
        }

        // if ($contract_name_id != 0) {
        //     $dates = $dates->where('contracts.contract_name_id', '=', $contract_name_id);
        // }
        if ($contract_name_id != 0) {
            //     $dates = $dates->where('contracts.contract_name_id', '=', $contract_name_id);
            // }
            // else if(gettype($contract_name_id) === 'array')
            // {
            $dates = $dates->whereIn('contracts.contract_name_id', [$contract_name_id]);
        }
        $dates = $dates->where('agreements.archived', '=', false)
            ->orderBy('contracts.physician_id')
            ->orderBy('physician_logs.practice_id')
            ->orderBy('contract_names.name')
            ->orderBy('physician_logs.date')
            // ->orderBy('physician_logs.next_approver_user',"DESC")
            ->first();
        return $dates;
    }

    public function getPerformanceReport()
    {
        if (!is_super_hospital_user() && !is_hospital_cfo() && !is_hospital_admin()) {
            return Redirect::route('dashboard.index')->with([
                'error' => Lang::get("Please login with correct role.")
            ]);
        }
        $user_id = Auth::user()->id;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('PerformanceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 1);
        });

        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $default_selected_manager = 0;
        $data['hospitals'] = Hospital::getHospitals($this->currentUser->id);
        $default_selected_hospital = key($data['hospitals']);
        //next($data['hospitals']);
        //  Log::info("hospitals",array($data['hospitals']));

        $data['agreements'] = Agreement::getAgreements($data['hospitals'], Request::input('hospital', $default_selected_hospital));
        // Log::info("agreements",array($data['agreements']));
        $default_agreement = key($data['agreements']);

        $data['practices'] = Practice::getPractice(Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);

        $data['physicians'] = Physician::getPhysician($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);

        $data['contract_types'] = ContractType::getContractTypesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians'], "", "");
        $default_contract_key = 0;

        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getPaymentTypeForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians']);

        $default_payment_key = 0;

        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //Chaitraly::For Contract Name for performance dashboard

        $data['contract_names'] = ContractName::getContractNamesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements'], $data['practices'], Request::input('practice', $default_practice), Request::input('physician', $default_physician), $data['physicians'], Request::input('payment_type', $default_payment_key), $data['payment_types'], Request::input('contract_type', $default_contract_key), $data['contract_types']);
        $data['contract_name'] = Request::input('contract_name', Request::input('contract_name', $default_contract_key));
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $levelOne = self::dataForPerformanceDashboard($this->currentUser->id, Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), 0, Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        $dates = $levelOne['dates'];
        $start_date = Request::input('start_date', $levelOne['dates']['start']);
        $end_date = Request::input('end_date', $levelOne['dates']['end']);

        $data['dates'] = $physicianLog->datesOptions($dates);
        $data['manager_filter'] = $default_selected_manager; // Request::input('manager_filter',$default_selected_manager);
        $data['hospital'] = $default_selected_hospital; // Request ::input('hospital',$default_selected_hospital);
        $data['agreement'] = $default_agreement; // Request::input('agreement',$default_agreement);
        $data['practice'] = $default_practice; // Request::input('practice',$default_practice);
        $data['physician'] = $default_physician;// Request::input('physician',$default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);

        $data['end_date'] = date("Y-m-t", strtotime(Request::input('end_date', $end_date))); // get the last day of the month to avoid date selection issue and data mismatch issue.

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);


        $data['report_id'] = Session::get('report_id');
        $hospital_ids = array_keys($data['hospitals']);
        // Log::info('data form',array($data));
        $data['form'] = view::make('hospitals/performance/indexAjaxRequest')->with($data)->render();
        $data['table'] = view::make('hospitals/performance/_reports_table')->with($data)->render();
        $hospital_ids = array_keys($data['hospitals']);
        return View::make('hospitals.performance.reports')->with($data);
    }

    public function getPerformanceApproverReport()
    {
        if (!is_super_hospital_user() && !is_hospital_cfo() && !is_hospital_admin()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager")
                ]);
            }
        }
        $user_id = Auth::user()->id;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('PerformanceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 2);
        });

        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $default_selected_manager = 0;
        $data['hospitals'] = Hospital::getHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));
        next($data['hospitals']);
        // Log::info("hospitals",array($data['hospitals']));
        $default_selected_hospital = key($data['hospitals']);
        $data['agreements'] = Agreement::getAgreements($this->currentUser->id, $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        //  Log::info("agreements",array($data['agreements']));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getPractice($this->currentUser->id, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getPhysician($this->currentUser->id, $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        $data['contract_types'] = ContractType::getContractTypesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = 0;
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));
        $data['payment_types'] = PaymentType::getPaymentTypeForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = 0;
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //Chaitraly::For Contract Name for performance dashboard
        $data['contract_names'] = ContractName::getContractNamesForPerformanceDashboard($this->currentUser->id, Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians'], Request::input('payment_type', $default_payment_key), $data['payment_types']);
        $data['contract_name'] = Request::input('contract_name', Request::input('contract_name', $default_contract_key));
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $levelOne = self::dataForPerformanceDashboard($this->currentUser->id, Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        $dates = $levelOne['dates'];
        $start_date = Request::input('start_date', $levelOne['dates']['start']);
        $end_date = Request::input('end_date', $levelOne['dates']['end']);

        $data['dates'] = $physicianLog->datesOptions($dates);
        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);

        $data['end_date'] = date("Y-m-t", strtotime(Request::input('end_date', $end_date))); // get the last day of the month to avoid date selection issue and data mismatch issue.

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);


        $data['report_id'] = Session::get('report_id');
        $hospital_ids = array_keys($data['hospitals']);
        $data['form'] = view::make('hospitals/performance/indexAjaxRequest')->with($data)->render();
        $data['table'] = view::make('hospitals/performance/_reports_table')->with($data)->render();
        $hospital_ids = array_keys($data['hospitals']);
        return View::make('hospitals.performance.approverReports')->with($data);
    }

    public function postPerformanceReport()
    {
        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;


        $reportdata = PerformanceReport::fetch_physician_data();
        //Allow all users to select multiple months for log report
        Artisan::call('reports:performancePhysicianReport', [
            "report_data" => $reportdata
        ]);

        if (!PerformancePhysicianReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PerformancePhysicianReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PerformancePhysicianReport::$message),
            'report_id' => PerformancePhysicianReport::$report_id,
            'report_filename' => PerformancePhysicianReport::$report_filename
        ]);
    }

    public function getPhysicianLogsList()
    {
        $request = $_POST;
        // Log::info("request for pop up table",array($request));
        $physican_activity_log = PhysicianLog::
        select(DB::raw("hospitals.name as hospital_name,contract_names.name as contract_name, CONCAT(physicians.first_name,' ',physicians.last_name) as physician_name, date_format(date,'%m/%d/%Y') as log_date, actions.name as action_name, duration, details "))
            ->join("actions", "actions.id", "=", "physician_logs.action_id")
            ->join("action_categories", "action_categories.id", "=", "actions.category_id")
            ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_logs.contract_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("payment_types", "payment_types.id", "=", "contracts.payment_type_id")
            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->where("agreements.hospital_id", "=", $request['hospital_id'])
            ->whereIn("agreements.id", $request['agreement_id'])
            ->whereIn("physician_practices.practice_id", $request['practice'])
            ->whereIn("physicians.id", $request['physician_id'])
            ->where("payment_types.id", "=", $request['payment_type'])
            ->whereIn("contract_types.id", $request['contract_type'])
            ->whereIn("contract_names.id", $request['contract_name'])
            ->where("physician_logs.date", ">=", $request['start_date'])
            ->where("physician_logs.date", "<=", $request['end_date'])
            ->where("actions.category_id", "=", $request['typeId'])
            ->get();

        $data_array = array();
        $all_data = array();
        $srNo = 1;
        foreach ($physican_activity_log as $activity_log) {
            $data_array[] = [
                "srNo" => $srNo,
                "hospital_name" => $activity_log->hospital_name,
                "contract_name" => $activity_log->contract_name,
                "physician_name" => $activity_log->physician_name,
                "log_date" => $activity_log->log_date,
                "duration" => $activity_log->duration,
                "action_name" => $activity_log->action_name,
                "details" => $activity_log->details,
            ];
            $srNo++;
        }
        if (Request::ajax()) {
            $data = $data_array;
            // Log::info("getPhysicianLogList() return::",array($data));
            return $data;
        }
    }


    public static function physicianLogs($user_id, $payment_type, $contract_type, $contract_name, $hospital_id, $agreement_id, $practice_id, $physician_id, $start_date = '', $end_date = '')
    {

        // Log::info("Log physician logs request",array($user_id,$payment_type, $contract_type,$contract_name, $hospital_id, $agreement_id , $practice_id , $physician_id, $start_date, $end_date));
        // $proxy_check_id = self::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        // $agreement_ids =ApprovalManagerInfo::select("agreement_id")
        //     ->where("is_deleted", "=", '0')
        //     //->where("user_id", "=", $user_id)
        //     ->whereIn("user_id",$proxy_check_id) //added this condition for checking with proxy approvers
        //     ->pluck("agreement_id");
        // $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
        //     ->where("contracts.default_to_agreement","=","1")
        //     ->whereIN("contracts.agreement_id",$agreement_ids);
        // $contracts_for_user = ApprovalManagerInfo::select("contract_id")->where("contract_id", ">", 0)
        //     ->where("is_deleted", "=", '0')
        //     //->where("user_id", "=", $user_id)
        //     ->whereIn("user_id",$proxy_check_id) //added this condition for checking with proxy approvers
        //     ->union($contract_with_default)->pluck("contract_id");
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        // Log::info(" prior_month_end_date",array( $prior_month_end_date));
        $logs = DB::table('physician_logs')
            ->select('physician_logs.id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            // ->where("physician_logs.signature", "=", 0)
            // ->where("physician_logs.approval_date", "=", "0000-00-00")
            // ->where(function ($query) use ($proxy_check_id) {
            //     //$query->where("physician_logs.next_approver_user", "=", $user_id);
            //     $query->whereIn("physician_logs.next_approver_user",$proxy_check_id); //added this condition for checking with proxy approvers
            // })
            ->whereNotIn("contracts.payment_type_id", [3, 5]);
        if ($start_date != '' && $end_date != '') {
            $logs = $logs->whereBetween('physician_logs.date', [mysql_date($start_date), mysql_date($end_date)]);
        } else {
            $logs = $logs->where("physician_logs.date", "<=", $prior_month_end_date);
        }
        if ($payment_type != 0) {
            $logs = $logs->where('contracts.payment_type_id', '=', $payment_type);
        }
        if ($contract_type != 0) {
            $logs = $logs->where('contracts.contract_type_id', '=', $contract_type);
        }
        if ($contract_name != 0) {
            $logs = $logs->where('contracts.contract_name_id', '=', $contract_name);
        }
        if ($hospital_id != 0) {
            $logs = $logs->where('agreements.hospital_id', '=', $hospital_id);
        }
        if ($agreement_id != 0) {
            $logs = $logs->where('contracts.agreement_id', '=', $agreement_id);
        }
        if ($practice_id != 0) {
            $logs = $logs->where('physician_logs.practice_id', '=', $practice_id);
        }
        if ($physician_id != 0) {
            $logs = $logs->where('contracts.physician_id', '=', $physician_id);
        }
        $logs = $logs->where('agreements.archived', '=', false)
            ->where(function ($query1) {
                $query1->whereNull("physician_logs.deleted_at");
                // ->orWhere("physician_logs.deleted_at", "=", "");
            })/*added for soft delete*/
            ->pluck('physician_logs.id');
        // Log::info("logs return",array($logs));
        return $logs;

    }

    function showPerformanceDashboard()
    {
        $default = [0 => 'All'];
        $user_id = Auth::user()->id;
        // $group_id = Auth::user()->group_id;

        if (is_hospital_user_healthSystem_user()) {
            $group_id = 7;
            $system_user = HealthSystemUsers::where('user_id', '=', $user_id)->first();
            $regions = $default + HealthSystemRegion::where('health_system_id', '=', $system_user->health_system_id)->orderBy('region_name')->pluck('region_name', 'id')->toArray();
            $default_region_key = key($regions);
        } else {
            $group_id = Auth::user()->group_id;
            $regions = $default;
            $default_region_key = key($regions);
        }

        $hospitals = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospital_list = array();
        foreach ($hospitals as $hospital_id => $hospital_name) {
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospital_list = $hospital;
            }
        }

        $hospital_list = $default + $hospital_list;
        $default_hospital_key = key($hospital_list);

        $practice_types = '{"1":"All","2":"Employed","3":"Independent"}';
        $practice_types = json_decode($practice_types, true);

        $contract_types = ContractType::getContractTypesForPerformansDashboard($user_id, $default_region_key, $default_hospital_key, 1, $group_id);
        $default_contract_type_key = key($contract_types);
        foreach($contract_types as $key=> $val)
        {
            if ($key == 2) {
                $default_contract_type_key = $key;
                break;
            }
        }
        $specialties = $default + Specialty::getSpecialtiesForPerformansDashboard($user_id, $default_region_key, $default_hospital_key, 1, $group_id, $default_contract_type_key);
        $default_specialty_key = key($specialties);
        $providers = $default + Physician::getProvidersForPerformansDashboard($user_id, $default_region_key, $default_hospital_key, 1, $group_id, $default_contract_type_key, $default_specialty_key);
        $default_provider_key = key($providers);

        $data = [
            'hospitals' => $hospital_list,
            'group_id' => $group_id,
            'regions' => $regions,
            'practice_types' => $practice_types,
            'contract_types' => $contract_types,
            'contract_type' => $default_contract_type_key,
            'specialties' => $specialties,
            'specialty' => $default_specialty_key,
            'providers' => $providers,
            'provider' => $default_provider_key
        ];

        return View::make('hospitals/performance/display_performance_dashboard_landing_page')->with($data);
    }

    /*
    @description:fetch contract types chart
    @return - json
    */
    public function getManagementContractTypeChart($region, $facility, $practice_type, $contract_type, $group_id)
    {
        if (Request::ajax()) {
            $data['type_data'] = ContractType::getManagementContractTypeChart(Auth::user()->id, $region, $facility, $practice_type, $contract_type, $group_id);
            return View::make('hospitals/performance/management_contract_type_chart')->with($data);
        }
    }

    /*
    @description:fetch specialty chart
    @return - json
    */
    public function getManagementSpecialtyChart($region, $facility, $practice_type, $specialty, $group_id, $contract_type)
    {
        if (Request::ajax()) {
            $data['type_data'] = Specialty::getManagementSpecialtyChart(Auth::user()->id, $region, $facility, $practice_type, $specialty, $group_id, $contract_type);
            return View::make('hospitals/performance/management_specialty_chart')->with($data);
        }
    }

    /*
    @description:fetch providers chart
    @return - json
    */
    public function getManagementProviderChart($region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty)
    {
        if (Request::ajax()) {
            $data['type_data'] = Physician::getManagementProviderChart(Auth::user()->id, $region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty);
            return View::make('hospitals/performance/management_providers_chart')->with($data);
        }
    }

    /*
    @description:fetch actual to expected time contract types chart
    @return - json
    */
    public function getActualToExpectedTimeContractTypeChart($region, $facility, $practice_type, $contract_type, $group_id)
    {
        if (Request::ajax()) {
            $data['type_data'] = ContractType::getActualToExpectedTimeContractTypeChart(Auth::user()->id, $region, $facility, $practice_type, $contract_type, $group_id);
            return View::make('hospitals/performance/actual_to_expected_contract_type_chart')->with($data);
        }
    }

    /*
    @description:fetch actual to expected time specialty chart
    @return - json
    */
    public function getActualToExpectedTimeSpecialtyChart($region, $facility, $practice_type, $specialty, $group_id, $contract_type)
    {
        if (Request::ajax()) {
            $data['type_data'] = Specialty::getActualToExpectedTimeSpecialtyChart(Auth::user()->id, $region, $facility, $practice_type, $specialty, $group_id, $contract_type);
            return View::make('hospitals/performance/actual_to_expected_specialty_chart')->with($data);
        }
    }

    /*
    @description:fetch actual to expected time providers chart
    @return - json
    */
    public function getActualToExpectedTimeProviderChart($region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty)
    {
        if (Request::ajax()) {
            $data['type_data'] = Physician::getActualToExpectedTimeProviderChart(Auth::user()->id, $region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty);
            return View::make('hospitals/performance/actual_to_expected_providers_chart')->with($data);
        }
    }

    /*
    @description:get contracts types for performance daashboard
    @return - json
    */
    public function getContractTypesForPerformansDashboard($region, $facility, $practice_type, $group_id)
    {
        if (Request::ajax()) {
            $data = ContractType::getContractTypesForPerformansDashboard(Auth::user()->id, "0", $facility, $practice_type, $group_id);
            return $data;
        }
    }

    /*
    @description:get specialties for performance daashboard
    @return - json
    */
    public function getSpecialtiesForPerformansDashboard($region, $facility, $practice_type, $group_id, $contract_type)
    {
        if (Request::ajax()) {
            $data = Specialty::getSpecialtiesForPerformansDashboard(Auth::user()->id, $region, $facility, $practice_type, $group_id, $contract_type);
            return $data;
        }
    }

    /*
    @description:get providers for performance daashboard
    @return - json
    */
    public function getProvidersForPerformansDashboard($region, $facility, $practice_type, $group_id, $contract_type, $specialty)
    {
        if (Request::ajax()) {
            $data = Physician::getProvidersForPerformansDashboard(Auth::user()->id, $region, $facility, $practice_type, $group_id, $contract_type, $specialty);
            return $data;
        }
    }

    /*
    @description:fetch Provider Comparison - Management Duty (Time) pop up
    @return - json
    */
    public function getManagementDutyPopUp($region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id, $category_id)
    {
        if (Request::ajax()) {
            $data = ContractType::getManagementDutyPopUp(Auth::user()->id, $region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id, $category_id);
            return $data;
        }
    }

    /*
    @description:fetch Provider Comparison - Actual to Expected (Time) pop up
    @return - json
    */
    public function getActualToExpectedPopUp($region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id)
    {
        if (Request::ajax()) {
            $data = ContractType::getActualToExpectedPopUp(Auth::user()->id, $region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id);
            return $data;
        }
    }

    public function downloadReport($report_id)
    {
        //Log::info("getReport start");
        $report = PerformanceReport::findOrFail($report_id);

        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $filename = performance_report_path($report);

        if (!file_exists($filename))
            App::abort(404);

        return FacadeResponse::download($filename);
        //Log::info("getReport end");
    }

    public function getDeleteReport($report_id)
    {
        $report = PerformanceReport::findOrFail($report_id);

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

    public function getPhysicianPerformanceReport()
    {

        $physicians = Request::input("physicians", null);

        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $user_id = Auth::user()->id;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $hospital_list = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospitals = array();
        foreach ($hospital_list as $hospital_id => $hospital_name) {
            $performance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
            if ($performance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospitals = $hospital;
            }
        }

        $hospital_list = $hospitals;

        if (Request::input("hospital", 0) > 0) {
            $default_hospital_key = Request::input("hospital", 0);
        } else {
            $default_hospital_key = key($hospital_list);
        }

        if (Request::input("contract_type", -1) != -1) {
            $contract_type_id = Request::input("contract_type", -1);
        } else {
            $contract_type_id = -1;
        }

        $data = $this->query('PerformanceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 1);
        });

        $data['group'] = Auth::user()->group_id;
        $data['table'] = view::make('hospitals/performance/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", $default_hospital_key);
        $data["contract_types"] = ContractType::getHospitalOptions($default_hospital_key, true);
        $data["contract_type"] = Request::input("contract_type", $contract_type_id);
        $data['agreements'] = Agreement::getHospitalAgreementDataForPhysiciansComplianceReports($physicians, $user_id, $data["facility"], $data["contract_type"]);
        $data['physicians'] = Physician::getHospitalPhysiciansDataForComplianceReports($user_id, $data["facility"], $contract_type_id);
        $data['form'] = view::make('hospitals/performance/_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return $data;
        }

        return View::make('hospitals/performance/reports')->with($data);
    }

    public function postPhysicianPerformanceReport()
    {
        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        // $reportdata = PerformanceReport::fetch_physician_data();
        $reportdata = PerformanceReport::fetch_physician_report_data();
        // Log::info('reportdata',array($reportdata));
        //Allow all users to select multiple months for log report
        Artisan::call('reports:performancePhysicianReport', [
            "report_data" => $reportdata
        ]);

        if (!PerformancePhysicianReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PerformancePhysicianReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PerformancePhysicianReport::$message),
            'report_id' => PerformancePhysicianReport::$report_id,
            'report_filename' => PerformancePhysicianReport::$report_filename
        ]);
    }

    public function getApproverPerformanceReport()
    {

        $approvers = Request::input("approvers", null);

        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $user_id = Auth::user()->id;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $hospital_list = Hospital::select('hospitals.*')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->where('hospitals.archived', '=', 0)
            ->pluck('name', 'id')->toArray();

        $hospital = array();
        $hospitals = array();
        foreach ($hospital_list as $hospital_id => $hospital_name) {
            $performance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital_id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
            if ($performance_on_off == 1) {
                $hospital[$hospital_id] = $hospital_name;
                $hospitals = $hospital;
            }
        }

        $hospital_list = $hospitals;

        if (Request::input("hospital", 0) > 0) {
            $default_hospital_key = Request::input("hospital", 0);
        } else {
            $default_hospital_key = key($hospital_list);
        }

        if (Request::input("contract_type", -1) != -1) {
            $contract_type_id = Request::input("contract_type", -1);
        } else {
            $contract_type_id = -1;
        }

        $data = $this->query('PerformanceReport', $options, function ($query, $options) use ($user_id) {
            return $query->where('created_by_user_id', '=', $user_id)
                ->where("report_type", "=", 2);
        });

        $data['group'] = Auth::user()->group_id;
        $data['table'] = view::make('hospitals/performance/_reports_table')->with($data)->render();
        $data['form_title'] = "Generate Report";
        $data['facilities'] = $hospital_list;
        $data["facility"] = Request::input("facility", $default_hospital_key);
        $data["contract_types"] = ContractType::getHospitalOptions($default_hospital_key, true);
        $data["contract_type"] = Request::input("contract_type", $contract_type_id);
        $data['agreements'] = Agreement::getHospitalAgreementDataForApproversComplianceReports($approvers, $user_id, $data["facility"], $data["contract_type"]);
        $data['approvers'] = Physician::getHospitalApproversDataForComplianceReports($user_id, $data["facility"], $contract_type_id);
        $data['form'] = view::make('hospitals/performance/_reports_form')->with($data)->render();
        $data['report_id'] = Session::get('report_id');

        if (Request::ajax()) {
            return $data;
        }

        return View::make('hospitals.performance.approverReports')->with($data);
    }

    public function postApproverPerformanceReport()
    {
        if (!(Auth::user()->group_id == Group::HOSPITAL_ADMIN) && !(Auth::user()->group_id == Group::SUPER_HOSPITAL_USER))
            App::abort(403);

        $default = [0 => 'All'];
        $user_id = Auth::user()->id;

        $reportdata = PerformanceReport::fetch_approver_report_data();
        // Log::info('reportdata',array($reportdata));
        //Allow all users to select multiple months for log report
        Artisan::call('reports:performanceApproverReport', [
            "report_data" => $reportdata
        ]);

        if (!PerformanceApproverReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PerformanceApproverReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PerformanceApproverReport::$message),
            'report_id' => PerformanceApproverReport::$report_id,
            'report_filename' => PerformanceApproverReport::$report_filename
        ]);
    }

    public function getPerformanceAgreementsByHospital($facility)
    {
        $user_id = Auth::user()->id;
        if (Request::ajax()) {
            $data['agreement'] = Agreement::getHospitalAgreementDataForComplianceReports($user_id, $facility);
            return $data;
        }
    }

    public function getPhysiciansByHospital($facility, $contract_type)
    {
        $user_id = Auth::user()->id;
        if (Request::ajax()) {
            $data['physicians'] = Physician::getHospitalPhysiciansDataForComplianceReports($user_id, $facility, $contract_type);
            return $data;
        }
    }

    public function getApproversByHospital($facility, $contract_type)
    {
        $user_id = Auth::user()->id;
        if (Request::ajax()) {
            $data['approvers'] = Physician::getHospitalApproversDataForComplianceReports($user_id, $facility, $contract_type);
            return $data;
        }
    }

    public function getTimePeriodByAgreements($facility, $agreements)
    {
        $user_id = Auth::user()->id;
        if (Request::ajax()) {
            $data['time_period'] = PerformanceReport::getTimePeriodByAgreements($facility, $agreements);
            return $data;
        }
    }

    public function getContractTypesForPerformanceReport($facility)
    {
        if (Request::ajax()) {
            $data['contract_types'] = ContractType::getHospitalOptions($facility, false);
            return $data;
        }
    }
}
