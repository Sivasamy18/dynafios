<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\ApprovalStatusReport;
use App\LogApproval;
use App\PhysicianLog;
use App\Hospital;
use App\Agreement;
use App\Practice;
use App\Physician;
use App\ContractType;
use App\PaymentType;
use App\ColumnPreferencesPaymentStatus;
use App\Group;
use App\UserSignature;
use App\ApprovalManagerType;
use App\ApprovalManagerInfo;
use App\ColumnPreferencesApprovalDashboard;
use App\ProxyApprovalDetails;
use App\User;
use App\PhysicianDeviceToken;
use App\HospitalReport;
use App\Services\NotificationService;
use App\HospitalContractSpendPaid;
use App\Jobs\UpdatePendingPaymentCount;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\PaymentStatusDashboard;
use App\Jobs\PaymentStatusDashboardExportToExcelReport;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function App\Start\is_approval_manager;
use function App\Start\is_hospital_admin;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;
use function App\Start\approval_report_path;
use function App\Start\payment_status_report_path;


class ApprovalController extends ResourceController
{

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    //display logs for approval
    public function index()
    {

        // Log::info('Approval dashboard start '. date('Y-m-d H:i:s'));
        if (!is_approval_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');

        // Log::info("start date -->",array($start_date));
        // Log::info("end date -->",array($end_date));
        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $data['manager_filters'] = $LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = key($data['manager_filters']);
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        // log::info('$data["agreements"]', array($data['agreements']));
        $default_agreement = key($data['agreements']);
        // Log::info('$default_agreement',array($default_agreement));
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList() + $custom_reason;
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //log::info('paytype',array($data['contract_types']));
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date,Request::input('report', false),Request::input('contract_id', null));
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        // Log::info("2nd time start date -->",array($start_date));
        // Log::info("end date -->",array($end_date));
        // Below line level one and level two is added by akash.
        $levelOne = $LogApproval->SummationDataLevelOne($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        // $levelTwo = $LogApproval->SummationDataLevelTwo($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),$data['contract_type'],Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
//		$data['sorted_items']= $LogApproval->sortedContractLogs($items['items']);
        /*$data['logs']= $items['logs'];
        $data['sorted_items']= $items['items'];*/
        $dates = $levelOne['dates'];
        $start_date = Request::input('start_date', $levelOne['dates']['start']);
        $end_date = Request::input('end_date', $levelOne['dates']['end']);
        // Log::info("3rd time start date -->",array($start_date));
        // Log::info("end date -->",array($end_date));

        // return $levelOne['LevelOne'];
        $data['level_one'] = $levelOne['LevelOne'];

        if (Request::input('report')) {
            if (Request::input('is_unapprove')) {

                $contract_id = Request::input('contract_id', 0);
                $start_date = $levelOne['dates']['start'];
                $end_date = $levelOne['dates']['end'];
                $reason_id = Request::input('unapprove_reason', 0);
                $custome_reason = Request::input('unapprove_custome_reason', '');
                // Log::Info("from controller", Array($reason_id));
                return $result = PhysicianLog::postUnapproveLogs(0, 0, $contract_id, 0, 0, $this->currentUser->id, $start_date, $end_date, $reason_id, $custome_reason);
            }

            $contract_for_user = [];
            if (Request::input('contract_id') != null) {
                array_push($contract_for_user, Request::input('contract_id'));
            }

            $items = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date, [], $contract_for_user, Request::input('report', false), Request::input('contract_id', null));
            return $items;
        }

        // $data['level_one_flag_approve'] = $levelOne['flagApprove'];
        // $data['level_two'] = $levelTwo['LevelTwo'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), '', '');
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $items['dates'];
        }*/
        $data['dates'] = $physicianLog->datesOptions($dates);
        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        // $data['end_date']= Request::input('end_date',$end_date);
        $data['end_date'] = date("Y-m-t", strtotime(Request::input('end_date', $end_date))); // get the last day of the month to avoid date selection issue and data mismatch issue.

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesApprovalDashboard();
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $data['column_preferences'] = $column_preferences_approval_dashboard;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }
        // Log::info('Approval dashboard end '. date('Y-m-d H:i:s'));

        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            /*$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type_id'),Request::input('hospital_id'),Request::input('agreement_id'),Request::input('practice_id'),Request::input('physician_id'),Request::input('start_date'),Request::input('end_date'));
            $data['sorted_items']= $LogApproval->sortedContractLogs($items);*/
            $view = view::make('approval/indexAjaxRequest')->with($data)->render();
            return Response::json($view);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        $data['report_id'] = Session::get('report_id');


        $hospital_ids = array_keys($data['hospitals']);
        $default_hospital_id = (isset($hospital_ids[1]) ? $hospital_ids[1] : $hospital_ids[0]);

        $data['assertation_popup_text'] = Hospital::get_assertation_text($default_hospital_id);

        return View::make('approval/index')->with($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    //display logs for approval
    public function getSummationDataLevelOne()
    {
        if (!is_approval_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');

        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $data['manager_filters'] = $LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = key($data['manager_filters']);
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList() + $custom_reason;
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //log::info('paytype',array($data['contract_types']));
        // Below line commented by akash.
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        // Below line is added by akash.
        $levelOne = $LogApproval->SummationDataLevelOne($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        //$levelTwo = $LogApproval->SummationDataLevelTwo($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),$data['contract_type'],Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$data['sorted_items']= $LogApproval->sortedContractLogs($items['items']);
        /*$data['logs']= $items['logs'];
        $data['sorted_items']= $items['items'];*/
        $dates = $levelOne['dates'];
        $start_date = Request::input('start_date', $levelOne['dates']['start']);
        $end_date = Request::input('end_date', $levelOne['dates']['end']);

        $data['level_one'] = $levelOne['LevelOne'];
        // $data['level_one_flag_approve'] = $levelOne['flagApprove'];
        //$data['level_two'] = $levelTwo['LevelTwo'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), '', '');
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $items['dates'];
        }*/
        //$data['dates']=$physicianLog->datesOptions($dates);
        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        $data['end_date'] = date("Y-m-t", strtotime(Request::input('end_date', $end_date))); // get the last day of the month to avoid date selection issue and data mismatch issue.

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesApprovalDashboard();
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $data['column_preferences'] = $column_preferences_approval_dashboard;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }

        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            /*$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type_id'),Request::input('hospital_id'),Request::input('agreement_id'),Request::input('practice_id'),Request::input('physician_id'),Request::input('start_date'),Request::input('end_date'));
            $data['sorted_items']= $LogApproval->sortedContractLogs($items);*/
            $view = view::make('approval/indexLevelOneAjaxRequest')->with($data)->render();
            return Response::json($view);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        $data['report_id'] = Session::get('report_id');
        return View::make('approval/index')->with($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    //display logs for approval
    public function getSummationDataLevelTwo()
    {
        if (!is_approval_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');

        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $data['manager_filters'] = $LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = key($data['manager_filters']);
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList() + $custom_reason;
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //log::info('paytype',array($data['contract_types']));
        // Below line commented by akash.
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        // Below line is added by akash.
        // $levelOne = $LogApproval->SummationDataLevelOne($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        $levelTwo = $LogApproval->SummationDataLevelTwo($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('payment_type', $default_payment_key), $data['contract_type'], Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date);
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$data['sorted_items']= $LogApproval->sortedContractLogs($items['items']);
        /*$data['logs']= $items['logs'];
        $data['sorted_items']= $items['items'];
        $dates = $items['dates'];*/


        // return $levelTwo['LevelTwo'];
        // $data['level_one'] = $levelOne['LevelOne'];
        $data['level_two'] = $levelTwo['LevelTwo'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), '', '');
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $items['dates'];
        }*/
        //$data['dates']=$physicianLog->datesOptions($dates);
        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        $data['end_date'] = Request::input('end_date', $end_date);
        $is_selected = Request::input('checked', false);
        if ($is_selected == 'true') {
            $is_selected = true;
        } else {
            $is_selected = false;
        }
        $data['is_level_one_selected'] = $is_selected;

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesApprovalDashboard();
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $data['column_preferences'] = $column_preferences_approval_dashboard;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }

        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            /*$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type_id'),Request::input('hospital_id'),Request::input('agreement_id'),Request::input('practice_id'),Request::input('physician_id'),Request::input('start_date'),Request::input('end_date'));
            $data['sorted_items']= $LogApproval->sortedContractLogs($items);*/
            $view = view::make('approval/indexLevelTwoAjaxRequest')->with($data)->render();
            return Response::json($view);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        $data['report_id'] = Session::get('report_id');
        return View::make('approval/index')->with($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    //display logs for approval
    public function getSummationDataLevelThree()
    {
        if (!is_approval_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Contract Manager / Financial Manager.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        $filter = Request::input('filter', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $end_date = Request::input('end_date', '');
        $contract_id = Request::input('contract_id', '');
        $contract_name_id = Request::input('contract_name_id', '');

        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $custom_reason = ['-1' => 'Custom Reason'];
        $data['filter'] = $filter;
        $data['manager_filters'] = $LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = key($data['manager_filters']);
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, Request::input('manager_filter', $default_selected_manager));
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList() + $custom_reason;
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));
        //log::info('paytype',array($data['contract_types']));
        // Below line commented by akash.
        // $summation_list =true; //This flag is for only fetching contract specific data for physician logs (summation dashboard).
        $level_three_flag = true;
        $items = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date, [], [], $report = true, $contract_id, $contract_name_id, $level_three_flag);
        // Below line is added by akash.
        // $levelOne = $LogApproval->SummationDataLevelOne($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        // $levelTwo = $LogApproval->SummationDataLevelTwo($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('payment_type',$default_payment_key),$data['contract_type'],Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date);
        //$data['sorted_items']= $LogApproval->sortedContractLogs($items['items']);
        $data['logs'] = $items['logs'];
        $data['sorted_items'] = $items['items'];
        //$dates = $items['dates'];

        // $data['level_one'] = $levelOne['LevelOne'];
        // $data['level_two'] = $levelTwo['LevelTwo'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsForApprover($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), '', '');
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $items['dates'];
        }*/
        //$data['dates']=$physicianLog->datesOptions($dates);
        $data['manager_filter'] = Request::input('manager_filter', $default_selected_manager);
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        $data['end_date'] = Request::input('end_date', $end_date);
        $is_selected = Request::input('checked', false);
        if ($is_selected == 'true') {
            $is_selected = true;
        } else {
            $is_selected = false;
        }
        $data['is_selected_level_two'] = $is_selected;

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesApprovalDashboard();
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $data['column_preferences'] = $column_preferences_approval_dashboard;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }

        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            /*$items = $LogApproval->LogsForApprover($this->currentUser->id,Request::input('manager_filter',$default_selected_manager),Request::input('contract_type_id'),Request::input('hospital_id'),Request::input('agreement_id'),Request::input('practice_id'),Request::input('physician_id'),Request::input('start_date'),Request::input('end_date'));
            $data['sorted_items']= $LogApproval->sortedContractLogs($items);*/
            $view = view::make('approval/indexLevelThreeAjaxRequest')->with($data)->render();
            return Response::json($view);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        $data['report_id'] = Session::get('report_id');
        return View::make('approval/index')->with($data);
    }

    public function paymentStatus()
    {

        if (!is_super_hospital_user() && !is_hospital_admin() && !is_practice_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Hospital User.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Hospital User.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Hospital User.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        $filter = Request::input('filter', 0);
        $status = Request::input('status', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');

        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        $data['filter'] = $filter;
        //$data['manager_filters']=$LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = -1;
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, $default_selected_manager);
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, $default_selected_manager, $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, $default_selected_manager, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, $default_selected_manager, $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList();
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));

        $LogApprovalStatus = $LogApproval->LogsApproverstatus($this->currentUser->id, $default_selected_manager, Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date, $status, false, 0);
        //$LogApprovalStatus = $LogApproval->LogsApproverstatus($this->currentUser->id,$default_selected_manager,Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date,$status);
        $data['items'] = $LogApprovalStatus["items"];
        $data['logs'] = $LogApprovalStatus['logs'];
        $dates = $LogApprovalStatus['dates'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsApproverstatus($this->currentUser->id,$default_selected_manager,Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date,$status);
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $LogApprovalStatus['dates'];
        }*/
        $data['dates'] = $physicianLog->datesOptions($dates);
        $data['manager_filter'] = $default_selected_manager;
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        $data['end_date'] = Request::input('end_date', $end_date);
        $data['status'] = $status;

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesPaymentStatus();
        $column_preferences_payment_status = ColumnPreferencesPaymentStatus::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_payment_status)) {
            $data['column_preferences'] = $column_preferences_payment_status;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }

        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            $view = view::make('approval/paymentStatusAjaxRequest')->with($data)->render();
            return Response::json($view);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        $data['report_id'] = Session::get('report_id');
        return View::make('approval/paymentStatusSuperHospitaUser')->with($data);
    }

    public function paymentStatusLevelOne()
    {

        if (!is_super_hospital_user() && !is_hospital_admin() && !is_practice_manager()) {
            if (Auth::user()->group_id == Group::Physicians) {
                $physician = Physician::where('email', '=', Auth::user()->email)->first();
                if ($physician) {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Hospital User.")
                    ]);
                } else {
                    return Redirect::route('physician.dashboard', $physician->id)->with([
                        'error' => Lang::get("Please login as a Hospital User.")
                    ]);
                }
            } else {
                return Redirect::route('dashboard.index')->with([
                    'error' => Lang::get("Please login as a Hospital User.")
                ]);
            }
        }

        //remove for approval tab to dashboard for all types also
        /*if(is_financial_manager() && !is_contract_manager()) {
            $filter = 2;
        }else{
            $filter = Request::input('filter',1);
        }*/
        // $filter = Request::input('filter',0);
        $status = Request::input('status', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $start_date = Request::input('start_date', '');
        $end_date = Request::input('end_date', '');
        $data['approver'] = Request::input('approver', 0);

        //$hospital = Hospital::findOrFail($hospital_id);
        $LogApproval = new LogApproval();
        $physicianLog = new PhysicianLog();
        // $data['filter']= $filter;
        //$data['manager_filters']=$LogApproval->getApprovalManagers($this->currentUser->id);
        $default_selected_manager = -1;
        $data['hospitals'] = Hospital::getApprovalUserHospitals($this->currentUser->id, $default_selected_manager);
        unset($data['hospitals'][0]);
        $default_selected_hospital = key($data['hospitals']);
        /*$contract_types = ContractType::getManagerOptions($this->currentUser->id,0,Request::input('hospital',$default_selected_hospital));
        $default_contract_key = key($contract_types);*/
        $data['agreements'] = Agreement::getApprovalUserAgreements($this->currentUser->id, $default_selected_manager, $data['hospitals'], Request::input('hospital', $default_selected_hospital));
        $default_agreement = key($data['agreements']);
        $data['practices'] = Practice::getApprovalUserPratice($this->currentUser->id, $default_selected_manager, Request::input('hospital', $default_selected_hospital), $data['hospitals'], Request::input('agreement', $default_agreement), $data['agreements']);
        $default_practice = key($data['practices']);
        $data['physicians'] = Physician::getApprovalUserPhysician($this->currentUser->id, $default_selected_manager, $data['practices'], Request::input('practice', $default_practice));
        $default_physician = key($data['physicians']);
        //$data['timePeriod'] = Physician::getApprovalUserTimePeriod($this->currentUser->id);
        $data['reasons'] = $LogApproval->getReasonsList();
        $data['contract_types'] = ContractType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_contract_key = key($data['contract_types']);
        $data['contract_type'] = Request::input('contract_type', Request::input('contract_type', $default_contract_key));

        $data['payment_types'] = PaymentType::getManagerOptions($this->currentUser->id, Request::input('manager_filter', $default_selected_manager), Request::input('hospital', $default_selected_hospital), Request::input('physician', $default_physician), $data['physicians']);
        $default_payment_key = key($data['payment_types']);
        $data['payment_type'] = Request::input('payment_type', Request::input('payment_type', $default_payment_key));

        $PaymentStatusLevelOne = $LogApproval->PaymentStatusLevelOne($this->currentUser->id, $default_selected_manager, Request::input('payment_type', $default_payment_key), Request::input('contract_type', $default_contract_key), Request::input('hospital', $default_selected_hospital), Request::input('agreement', $default_agreement), Request::input('practice', $default_practice), Request::input('physician', $default_physician), $start_date, $end_date, $status, false, Request::input('approver', 0));
        //$LogApprovalStatus = $LogApproval->LogsApproverstatus($this->currentUser->id,$default_selected_manager,Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date,$status);
        // $data['items'] = $LogApprovalStatus["items"];
        // $data['logs']= $LogApprovalStatus['logs'];
        // $dates = $LogApprovalStatus['dates'];

        /*if($start_date != '' && $end_date != '') {
            $datesWithLog = $LogApproval->LogsApproverstatus($this->currentUser->id,$default_selected_manager,Request::input('contract_type',$default_contract_key),Request::input('hospital',$default_selected_hospital),Request::input('agreement',$default_agreement),Request::input('practice',$default_practice),Request::input('physician',$default_physician),$start_date,$end_date,$status);
            $dates = $datesWithLog['dates'];
        }else{
            $dates = $LogApprovalStatus['dates'];
        }*/
        // log::info('$PaymentStatusLevelOne', array($PaymentStatusLevelOne));
        // log::info('$dates', array($dates));

        $dates['start'] = $PaymentStatusLevelOne ? $PaymentStatusLevelOne->min('period_min_date') : date("m/d/Y", strtotime(now()));
        $dates['end'] = $PaymentStatusLevelOne ? $PaymentStatusLevelOne->max('period_max_date') : date("m/d/Y", strtotime(now()));
        $data['PaymentStatusLevelOne'] = $PaymentStatusLevelOne;
        $data['dates'] = $physicianLog->datesOptions($dates);
        $data['manager_filter'] = $default_selected_manager;
        $data['hospital'] = Request::input('hospital', $default_selected_hospital);
        $data['agreement'] = Request::input('agreement', $default_agreement);
        $data['practice'] = Request::input('practice', $default_practice);
        $data['physician'] = Request::input('physician', $default_physician);
        $data['start_date'] = Request::input('start_date', $start_date);
        $data['end_date'] = Request::input('end_date', $end_date);
        $data['status'] = $status;

        /* $hospital_ids = [];
        foreach($data['hospitals'] as $key => $value){
            $hospital_ids [] = $key;
        }
        $approvers = User::select('users.id as id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id');
            if($data['hospital'] == 0){
                $approvers = $approvers->whereIn('hospital_user.hospital_id', $hospital_ids);
            } else{
                $approvers = $approvers->where('hospital_user.hospital_id', $data['hospital']);
            }
            $approvers = $approvers->orderBy('name')
                ->pluck('name', 'id');

        $data['approvers'] = $approvers; */

        $data['invoice_dashboard_display'] = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);

        $data['column_preferences'] = new ColumnPreferencesPaymentStatus();
        $column_preferences_payment_status = ColumnPreferencesPaymentStatus::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_payment_status)) {
            $data['column_preferences'] = $column_preferences_payment_status;
        } else {
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }

        $data['report_id'] = Session::get('report_id');

        /*
            @ajax request for filtering payment status page - starts
        */

        // return View::make('approval/underMaintenance')->with($data);

        if (Request::ajax()) {
            // $view = view::make('approval/paymentStatusAjaxRequest')->with($data)->render();
            $view = view::make('approval/paymentStatusLevelOne')->with($data)->render();
            return Response::json(['data' => $data, 'level_one_view' => $view]);
        }
        /*
            @ajax request for filtering payment status page - ends
        */
        // $data['report_id'] = Session::get('report_id');
        // return View::make('approval/paymentStatusSuperHospitaUser')->with($data);
        return View::make('approval/paymentStatusIndex')->with($data);
    }

    public function paymentStatusLevelTwo()
    {
        $data = array();
        $physician_id = Request::input('physician_id', 0);
        $practice_id = Request::input('practice_id', 0);
        $period_min_date = Request::input('period_min_date', '');
        $period_max_date = Request::input('period_max_date', '');
        $status = Request::input('status', 0);
        $payment_type = Request::input('payment_type', 0);
        $contract_type = Request::input('contract_type', 0);
        $hospital_id = Request::input('hospital_id', 0);
        $agreement_id = Request::input('agreement_id', 0);
        $approver = Request::input('approver', 0);

        if ($physician_id != 0 && $practice_id != 0 && $period_min_date != '' && $period_max_date != '') {
            $payment_status_level_two_obj = LogApproval::PaymentStatusLevelTwo($physician_id, $practice_id, $period_min_date, $period_max_date, $status, $payment_type, $contract_type, $hospital_id, $agreement_id, $approver);
            $data['level_two'] = $payment_status_level_two_obj;
        } else {
            Log::Info('Something went wrong', array($physician_id, $practice_id, $period_min_date, $period_max_date));
            return false;
        }
        // Log::Info('data', array($data));
        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            $data['status'] = $status;
            $view = view::make('approval/paymentStatusLevelTwoAjax')->with($data)->render();
            return Response::json($view);
        }
    }

    public function paymentStatusLevelThree()
    {
        $data = array();
        $physician_id = Request::input('physician_id', 0);
        $contract_id = Request::input('contract_id', 0);
        $practice_id = Request::input('practice_id', 0);
        $period_min_date = Request::input('period_min_date', '');
        $period_max_date = Request::input('period_max_date', '');
        $payment_type_id = Request::input('payment_type_id', 0);
        $contract_type = Request::input('contract_type', 0);
        $hospital = Request::input('hospital', 0);
        $agreement = Request::input('agreement', 0);
        $practice = Request::input('practice', 0);
        $status = Request::input('status', 0);
        $approver = Request::input('approver', 0);

        $default_selected_manager = -1;

        if ($physician_id != 0 && $practice_id != 0 && $period_min_date != '' && $period_max_date != '' && $payment_type_id != 0) {
            $LogApproval = new LogApproval();
            $LogApprovalStatus = $LogApproval->LogsApproverstatus($this->currentUser->id, $default_selected_manager, $payment_type_id, $contract_type, $hospital, $agreement, $practice_id, $physician_id, $period_min_date, $period_max_date, $status, $report = true, $contract_id, $approver);
            $data['items'] = $LogApprovalStatus["items"];
            $data['logs'] = $LogApprovalStatus['logs'];

            $data['column_preferences'] = new ColumnPreferencesPaymentStatus();
            $column_preferences_payment_status = ColumnPreferencesPaymentStatus::where('user_id', '=', Auth::user()->id)->first();
            if (!is_null($column_preferences_payment_status)) {
                $data['column_preferences'] = $column_preferences_payment_status;
            } else {
                $data['column_preferences']->date = true;
                $data['column_preferences']->hospital = true;
                $data['column_preferences']->agreement = true;
                $data['column_preferences']->contract = true;
                $data['column_preferences']->practice = true;
                $data['column_preferences']->physician = true;
                $data['column_preferences']->log = true;
                $data['column_preferences']->details = true;
                $data['column_preferences']->duration = true;
                $data['column_preferences']->physician_approval = true;
                $data['column_preferences']->lvl_1 = true;
                $data['column_preferences']->lvl_2 = true;
                $data['column_preferences']->lvl_3 = true;
                $data['column_preferences']->lvl_4 = true;
                $data['column_preferences']->lvl_5 = true;
                $data['column_preferences']->lvl_6 = true;
            }
        } else {
            Log::Info('Something went wrong', array($physician_id, $practice_id, $period_min_date, $period_max_date));
            return false;
        }
        // Log::Info('data---------------', array($data));
        /*
            @ajax request for filtering payment status page - starts
        */
        if (Request::ajax()) {
            $view = view::make('approval/paymentStatusLevelThreeAjax')->with($data)->render();
            return Response::json($view);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function approveLog()
    {
        $process_status_error = 0;
        $user_id = Auth::user()->id;
        $status = 1;
        $log_ids = explode(',', Request::input("log_ids"));
        $rejected = explode(',', Request::input("rejected"));
        $reason = Request::input('reason');
        $manager_types = explode(',', Request::input("manager_types"));
        $physician_log = new PhysicianLog();
        $approveRejectionByType = array();
        $rejectedFromManager = array();
        $approvedFromManager = array();
        $approval_manager_types = ApprovalManagerType::all();
        $approvalManagerInfo = array();
        $approvalManager = new ApprovalManagerInfo();
        $hospital_id = 0;

        $hospital_ids = array();

        foreach ($rejected as $rejected_log) {
            if (!in_array($rejected_log, $rejectedFromManager)) {
                $rejectedFromManager[] = $rejected_log;
            }
        }

        foreach ($log_ids as $approveded_log) {
            $hospital_obj = PhysicianLog::select('hospitals.id as hospital_id')
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->where('physician_logs.id', '=', $approveded_log)
                ->first();

            if ($hospital_obj) {
                $hospital_id = $hospital_obj->hospital_id;
                if (!in_array($hospital_id, $hospital_ids)) {
                    array_push($hospital_ids, $hospital_id);
                }
            }

            if (!in_array($approveded_log, $approvedFromManager)) {
                $approvedFromManager[] = $approveded_log;
            }
        }

        if (count($rejectedFromManager) > 0 || count($approvedFromManager)) {
            $approveRejectionByType[0] = ['approved' => implode(',', $approvedFromManager),
                'rejected' => implode(',', $rejectedFromManager),
                'manager' => ''];
        }

        foreach ($approveRejectionByType as $key => $value) {
//			 $role = $key +1 ;
            $role = 0; // This change is done for setting approval manager type to none.
            $manager = $value['manager'];
            if ($value['rejected'] != '') {
                $this->sendRejectNotification(explode(",", $value['rejected']), $manager);
            }
            if (!Request::input('signature_id')) {
                $signature = UserSignature::where('user_id', '=', $user_id)->orderBy("created_at", "desc")->first();
                $signArray = explode(',', Request::input("signature"), 2);
                $signature_path = $signArray[1];
                if ($signature && Request::input("signature")) {
                    //not update for maintain history
                    // $signature_update= UserSignature::where('user_id','=',$user_id)->update(array('signature_path' => $signature_path));
                    // if (!$signature_update) {
                    // 	return 0;
                    // }
                    $signature_new = new UserSignature();
                    $signature_new->user_id = $user_id;
                    $signature_new->signature_path = $signature_path;
                    if (!$signature_new->save()) {
                        $process_status_error++;
                    }
                    $signature = UserSignature::where('user_id', '=', $user_id)->orderBy("created_at", "desc")->first();
                } else {
                    $signature_new = new UserSignature();
                    $signature_new->user_id = $user_id;
                    $signature_new->signature_path = $signature_path;
                    if (!$signature_new->save()) {
                        $process_status_error++;
                    }
                    $signature = UserSignature::where('user_id', '=', $user_id)->orderBy("created_at", "desc")->first();
                }
                if ($log_ids == 0 && $rejected == 0) {
                    $process_status_error = 1;
                } else {
                    $physician_log->approveLogs(explode(",", $value['approved']), explode(",", $value['rejected']), $signature, $role, $user_id, $status, $reason);
                    $process_status_error = 0;
                }

            } else {
                $signature = UserSignature::where('signature_id', '=', Request::input('signature_id'))->first();
                $physician_log->approveLogs(explode(",", $value['approved']), explode(",", $value['rejected']), $signature, $role, $user_id, $status, $reason);
                $process_status_error = 0;
            }
            if ($value['approved'] != '') {
                $approvalManagerInfo = $approvalManager->approvalNotifymail(explode(",", $value['approved']), $role, $approvalManagerInfo);
            }
        }
        if ($process_status_error > 0) {
            return 0;
        } else {
            if (count($approvalManagerInfo)) {
                // $this->sendNextLevelReminder($approvalManagerInfo);
            }
            if (count($hospital_ids) > 0) {
                foreach ($hospital_ids as $hospital_id) {
                    if ($hospital_id != 0) {
//                        log::info('Before approveLog ->', array(date('m-d-Y h:m:s'), $hospital_id));
                        UpdatePendingPaymentCount::dispatch($hospital_id);
//                        log::info('After approveLog ->', array(date('m-d-Y h:m:s'), $hospital_id));
                    }
                }
            }
            return 1;
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    // post logs for approval and rejection on signature screen
    private function sendRejectNotification($rejected, $manager)
    {
        $physicians = new PhysicianLog();
        $physicians_data = $physicians->rejectedNotifymail($rejected);
        $user = User::findOrFail(Auth::user()->id);
        if (count($physicians_data) > 0) {
            foreach ($physicians_data as $physician) {
                $data["email"] = $physician->email;
                $data["name"] = $physician->first_name . " " . $physician->last_name;
                $data["manager"] = $manager;
                // $data["type"]= $physician->type;
                $deviceToken = $physician->device_token;
                //$deviceToken = "7781e1644ec11a123c62d54a8c080833778ee51dfa154d9723a174552210152c";
                $data["type"] = EmailSetup::RESUBMIT_LOG_REMINDER_FOR_PHYSICIAN;
                $data['subject_param'] = [
                    'name' => '',
                    'date' => '',
                    'month' => '',
                    'year' => '',
                    'requested_by' => '',
                    'manager' => $manager,
                    'subjects' => ''
                ];
                if ($deviceToken != "" && $physician->device_type != "") {
                    $message = [
                        //'title' => 'This is the title',
                        //'body' => 'This is the body'
                        'content-available' => 1,
                        'sound' => 'example.aiff',

                        'actionLocKey' => 'Action button title!',
                        'action_flag' => 2,
                        'log_count' => 10,
                        'locKey' => 'localized key',
                        'locArgs' => array(
                            'localized args',
                            'localized args',
                        ),
                        'launchImage' => 'image.jpg',
                        'message' => 'Your logs has been rejected by the ' . $manager . '. Please review and re-submit the logs.'
                    ];

                    // OneSignal push notification code is added here by akash
                    $push_msg = 'Your log(s) have been rejected by the ' . $manager . '. Please review and re-submit the logs.';
                    $notification_for = 'REJECTED';

                    try {
                        if ($physician->device_type == PhysicianDeviceToken::iOS) {
                            $title = 'Your log(s) have been rejected';
                            $body = 'Your log(s) have been rejected by the ' . $manager . '. Please review and re-submit the logs.';

                            $result = NotificationService::sendPushNotificationForIOS($deviceToken, $title, $body);

                        } elseif ($physician->device_type == PhysicianDeviceToken::Android) {
                            $result = NotificationService::sendPushNotificationForAndroid($deviceToken, $message);
                        }
                        $result = NotificationService::sendOneSignalPushNotification($deviceToken, $push_msg, $notification_for);
                    } catch (Exception $e) {
                        Log::info("error", array($e));
                    }
                }

                $log_details = PhysicianLog::select('contract_names.name as contract_name', 'physician_logs.date as log_date', 'actions.name as activity', 'physician_logs.duration as duration', 'physician_logs.details as details', 'rejected_log_reasons.reason as reason')
                    ->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                    ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                    ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
                    ->join('rejected_log_reasons', 'rejected_log_reasons.id', '=', 'log_approval.reason_for_reject')
                    ->join('users', 'users.id', '=', 'log_approval.user_id')
                    ->whereIn('physician_logs.id', $rejected)
                    ->where('physician_logs.physician_id', '=', $physician->id)
                    ->where('log_approval.user_id', '=', Auth::user()->id)
                    ->get();

                $data['with'] = [
                    'name' => $physician->first_name . ' ' . $physician->last_name,
                    'type' => $physician->type,
                    'manager' => $manager,
                    'manager_name' => $user->first_name . ' ' . $user->last_name,
                    'log_details' => $log_details
                ];

                EmailQueueService::sendEmail($data);
            }
        }
    }


    public function save()
    {
        if (!is_approval_manager())
            App::abort(403);

//		if(is_financial_manager() && !is_contract_manager()) {
//			$filter = 1;
//		}else{
//			$filter = Request::input('filter',0);
//		}
        /*$approve = Request::input('approved', array());
        $rej = Request::input('rejected', array());*/
        $approve = Request::input('approve_logs', '');
        $rej = Request::input('reject_logs', '');

        $manager_types = array();
        $logs_manager_types = array();
        $approval_manager_type_info = new ApprovalManagerInfo();

        /*$data['log_ids']=implode(',',$approve);
        $data['rejected']=implode(',',$rej);
        $data['rejected_with_reasons'] = 0;*/
        $data['log_ids'] = $approve;
        $data['rejected'] = $rej;
        $data['rejected_with_reasons'] = 0;
        if ($approve != '') {
            $approved = explode(",", $approve);
        } else {
            $approved = array();
        }
        if ($rej != '') {
            $rejected = explode(",", $rej);
        } else {
            $rejected = array();
        }
        if (count($rejected) > 0) {
            $rejected_with_reasons = array();
            foreach ($rejected as $rejected_id) {
                $reject_log_reason = $rejected_id . '_' . Request::input('reject_reason_' . $rejected_id, 0);
                if (Request::input('reject_reason_' . $rejected_id) == '-1') {
                    $rejected_log_reason_text = Request::input('reject_reason_text_' . $rejected_id, 0);
                    $reject_log_reason_id = LogApproval::saveRejectedLogReason($rejected_log_reason_text);
                    $reject_log_reason = $rejected_id . '_' . $reject_log_reason_id;
                }
                $rejected_with_reasons[] = $reject_log_reason;
                $manager_types[] = $rejected_id . '_' . Request::input('reject_manager_type_' . $rejected_id, 0);
            }
            $data['rejected_with_reasons'] = implode(',', $rejected_with_reasons);
        } else {
            $data['rejected'] = 0;
        }

        if (count($approved) > 0) {
            foreach ($approved as $approveded_log) {
                $log_manager_type = $approval_manager_type_info->getLogManagerType($approveded_log);
                $manager_types[] = $approveded_log . '_' . $log_manager_type;
                $logs_manager_types[$approveded_log] = $log_manager_type;
            }
        }
        if (count($manager_types) > 0) {
            $data['manager_types'] = implode(',', $manager_types);
        } else {
            $data['manager_types'] = 0;
        }
        $sign = UserSignature::where('user_id', '=', Auth::user()->id)->orderBy("created_at", "desc")->first();
        if (count($approved) > 0) {
            if ($sign) {
                $data['signature'] = $sign;
                return View::make('approval/signatureApprove')->with($data);
            } else {
                return View::make('approval/signatureApprove_edit')->with($data);
            }
        } elseif ($rejected > 0) {
            $user_id = Auth::user()->id;
            $status = 1;
            if ($data['log_ids'] != '') {
                $log_ids = explode(',', $data['log_ids']);
            } else {
                $log_ids = array();
            }
            if ($data['rejected'] != '') {
                $rejected_logs = explode(',', $data['rejected']);
            } else {
                $rejected_logs = array();
            }
            $reason = $data['rejected_with_reasons'];
            $physician_log = new PhysicianLog();
            $rejectionByType = array();
            $rejectedFromManager = array();
            $approvedFromManager = array();
            $approval_manager_types = ApprovalManagerType::all();
            // foreach($approval_manager_types as $typeManager){
            // 	unset($rejectedFromManager);
            // 	$rejectedFromManager = array();
            // 	unset($approvedFromManager);
            // 	$approvedFromManager = array();
            // 	foreach ($rejected_logs as $rejected_log){
            // 		if(Request::input('reject_manager_type_'.$rejected_log,0) == $typeManager->approval_manager_type_id){
            // 			$rejectedFromManager[]=$rejected_log;
            // 		}
            // 	}
            // 	foreach ($log_ids as $approveded_log){
            // 		if($logs_manager_types[$approveded_log] == $typeManager->approval_manager_type_id){
            // 			$approvedFromManager[]=$approveded_log;
            // 		}
            // 	}
            // 	if(count($rejectedFromManager) > 0 || count($approvedFromManager)){
            // 		$rejectionByType[$typeManager->approval_manager_type_id] = ['approved' => implode(',',$approvedFromManager),
            // 			'rejected' => implode(',',$rejectedFromManager),
            // 			'manager' => $typeManager->manager_type];
            // 	}
            // }

            if (count($rejected_logs) > 0 || count($log_ids)) {
                $rejectionByType[0] = ['approved' => $log_ids,
                    'rejected' => $rejected_logs,
                    'manager' => ""];
            }
            foreach ($rejectionByType as $key => $value) {
                $role = $key + 1;
                $manager = $value['manager'];
                $physician_log->approveLogs($value['approved'], $value['rejected'], $sign, $role, $user_id, $status, $reason);
                if ($value['rejected'] != '') {
                    $this->sendRejectNotification($value['rejected'], $manager);
                }
            }
            return Redirect::back()->with([
                'success' => Lang::get('All logs are rejected')
            ]);
        }

    }

    /**
     * Display the last updated signature.
     *

     */
    public function getSignature()
    {
        //
        $sign = UserSignature::where('user_id', '=', Auth::user()->id)->orderBy("created_at", "desc")->first();
        if ($sign) {
            $data['signature'] = $sign->signature_path;
            return View::make('approval/signature')->with($data);
        } else {
            return View::make('approval/signature_edit');
        }
    }

    /**
     * Edit the last updated signature.
     *

     */
    public function changeSignature()
    {
        //
        return View::make('approval/signature_edit');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function editSignature($log_ids, $rejected, $rejected_with_reasons, $manager_types)
    {
        if (!is_approval_manager())
            App::abort(403);

//			$data['log_ids']=Request::input('log_ids');
//			$data['rejected']=Request::input('rejected');
//			$data['filter']=Request::input('filter');
        $data['log_ids'] = $log_ids;
        $data['rejected'] = $rejected;
        //Log::info($rejected_with_reasons);
        $data['rejected_with_reasons'] = $rejected_with_reasons;
        $data['manager_types'] = $manager_types;
//			$data['filter']=$filter;
//			$data['hospital_id']=$hospital_id;
        return View::make('approval/signatureApprove_edit')->with($data);
    }

    public function approvalStatusReport()
    {
        /*if (!is_approval_manager())
            App::abort(403);*/


        $selected_manager = Request::input('export_manager_filter', 0);
        $payment_type = Request::input('export_payment_type', 0); // for payment type
        $contract_type = Request::input('export_contract_type', 0);
        $selected_hospital = Request::input('export_hospital', 0);
        $selected_agreement = Request::input('export_agreement', 0);
        $selected_practice = Request::input('export_practice', 0);
        $selected_physician = Request::input('export_physician', 0);
        $start_date = Request::input('export_start_date', '');
        $end_date = Request::input('export_end_date', '');
        $report_type = Request::input('export_report_type', 0);
        $status = Request::input('export_status', 0);
        $LogApproval = new LogApproval();

        $show_calculated_payment = false;

//        $report_type == 0 (Approval Dashboard Report) && $report_type == 1 (Payment Status Dashboard Report)
        if ($report_type == 0) {
//			$dataWithDate = $LogApproval->LogsForApprover($this->currentUser->id, $selected_manager,$payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date,[], [],true);
            $dataWithDate = $LogApproval->ApproverLogsReport($this->currentUser->id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, [], [], true);
//            log::debug('$dataWithDate', array($dataWithDate));
//			$data = $dataWithDate['items'];
            $data = $dataWithDate['AppprovalLogBreakup'];
            $column_preference_details = ColumnPreferencesApprovalDashboard::where("user_id", '=', $this->currentUser->id)->first();
            if ($column_preference_details) {
                $show_calculated_payment = $column_preference_details->calculated_payment;
            }

        } else {
            $dataWithDate = $LogApproval->LogsApproverstatus($this->currentUser->id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, $status, true, 0);
            $data = $dataWithDate['items'];
        }

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //	log::info("timestamp",array($timestamp));

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

        //	log::info("post localtimeZone",array($localtimeZone));
//        log::info('$data', array($data));

        Artisan::call('reports:approvalstatus', [
            "report_data" => $data,
            "report_type" => $report_type,
            "localtimeZone" => $localtimeZone,
            "show_calculated_payment" => $show_calculated_payment
        ]);

        if (!ApprovalStatusReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(ApprovalStatusReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(ApprovalStatusReport::$message),
            'report_id' => ApprovalStatusReport::$report_id,
            'report_filename' => ApprovalStatusReport::$report_filename
        ]);
    }

    public function getStatusReport($report_id)
    {

        $approval_report = HospitalReport::findOrFail($report_id);

        /*if (!is_approval_manager())
            App::abort(403);*/

        $filename = approval_report_path($approval_report);

        if (!file_exists($filename)) {
            $filename = payment_status_report_path($approval_report);
            if (!file_exists($filename)) {
                App::abort(404);
            }
        }

        return Response::download($filename);
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

    public function getAllLogIdsForApproval()
    {
        $log_ids = LogApproval::logIdsForApproval($this->currentUser->id, Request::input('manager_filter', 0), Request::input('payment_type', 0), Request::input('contract_type', 0), Request::input('hospital', 0), Request::input('agreement', 0), Request::input('practice', 0), Request::input('physician', 0), Request::input('start_date', ''), Request::input('end_date', ''));
        return $log_ids;
    }

    public function columnPreferencesPaymentStatus()
    {
        $data['column_preferences'] = new ColumnPreferencesPaymentStatus();
        $column_preferences_payment_status = ColumnPreferencesPaymentStatus::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_payment_status)) {
            $data['column_preferences'] = $column_preferences_payment_status;
        } else {
            $data['column_preferences']->user_id = Auth::user()->id;
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
        }
        return View::make('approval/columnPreferencesPaymentStatus')->with($data);
    }

    public function postColumnPreferencesPaymentStatus()
    {

        $column_preferences_payment_status = ColumnPreferencesPaymentStatus::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_payment_status)) {
            $column_preferences_payment_status->date = (is_null(Request::input('date_on_off')) ? 0 : Request::input('date_on_off'));
            $column_preferences_payment_status->hospital = (is_null(Request::input('hospital_on_off')) ? 0 : Request::input('hospital_on_off'));
            $column_preferences_payment_status->agreement = (is_null(Request::input('agreement_on_off')) ? 0 : Request::input('agreement_on_off'));
            $column_preferences_payment_status->contract = (is_null(Request::input('contract_on_off')) ? 0 : Request::input('contract_on_off'));
            $column_preferences_payment_status->practice = (is_null(Request::input('practice_on_off')) ? 0 : Request::input('practice_on_off'));
            $column_preferences_payment_status->physician = (is_null(Request::input('physician_on_off')) ? 0 : Request::input('physician_on_off'));
            $column_preferences_payment_status->log = (is_null(Request::input('log_on_off')) ? 0 : Request::input('log_on_off'));
            $column_preferences_payment_status->details = (is_null(Request::input('details_on_off')) ? 0 : Request::input('details_on_off'));
            $column_preferences_payment_status->duration = (is_null(Request::input('duration_on_off')) ? 0 : Request::input('duration_on_off'));
            $column_preferences_payment_status->physician_approval = (is_null(Request::input('physician_approval_on_off')) ? 0 : Request::input('physician_approval_on_off'));
            $column_preferences_payment_status->lvl_1 = (is_null(Request::input('lvl_1_on_off')) ? 0 : Request::input('lvl_1_on_off'));
            $column_preferences_payment_status->lvl_2 = (is_null(Request::input('lvl_2_on_off')) ? 0 : Request::input('lvl_2_on_off'));
            $column_preferences_payment_status->lvl_3 = (is_null(Request::input('lvl_3_on_off')) ? 0 : Request::input('lvl_3_on_off'));
            $column_preferences_payment_status->lvl_4 = (is_null(Request::input('lvl_4_on_off')) ? 0 : Request::input('lvl_4_on_off'));
            $column_preferences_payment_status->lvl_5 = (is_null(Request::input('lvl_5_on_off')) ? 0 : Request::input('lvl_5_on_off'));
            $column_preferences_payment_status->lvl_6 = (is_null(Request::input('lvl_6_on_off')) ? 0 : Request::input('lvl_6_on_off'));
            $column_preferences_payment_status->save();
        } else {
            $column_preferences = new ColumnPreferencesPaymentStatus();
            $column_preferences->user_id = Auth::user()->id;
            $column_preferences->date = (is_null(Request::input('date_on_off')) ? 0 : Request::input('date_on_off'));
            $column_preferences->hospital = (is_null(Request::input('hospital_on_off')) ? 0 : Request::input('hospital_on_off'));
            $column_preferences->agreement = (is_null(Request::input('agreement_on_off')) ? 0 : Request::input('agreement_on_off'));
            $column_preferences->contract = (is_null(Request::input('contract_on_off')) ? 0 : Request::input('contract_on_off'));
            $column_preferences->practice = (is_null(Request::input('practice_on_off')) ? 0 : Request::input('practice_on_off'));
            $column_preferences->physician = (is_null(Request::input('physician_on_off')) ? 0 : Request::input('physician_on_off'));
            $column_preferences->log = (is_null(Request::input('log_on_off')) ? 0 : Request::input('log_on_off'));
            $column_preferences->details = (is_null(Request::input('details_on_off')) ? 0 : Request::input('details_on_off'));
            $column_preferences->duration = (is_null(Request::input('duration_on_off')) ? 0 : Request::input('duration_on_off'));
            $column_preferences->physician_approval = (is_null(Request::input('physician_approval_on_off')) ? 0 : Request::input('physician_approval_on_off'));
            $column_preferences->lvl_1 = (is_null(Request::input('lvl_1_on_off')) ? 0 : Request::input('lvl_1_on_off'));
            $column_preferences->lvl_2 = (is_null(Request::input('lvl_2_on_off')) ? 0 : Request::input('lvl_2_on_off'));
            $column_preferences->lvl_3 = (is_null(Request::input('lvl_3_on_off')) ? 0 : Request::input('lvl_3_on_off'));
            $column_preferences->lvl_4 = (is_null(Request::input('lvl_4_on_off')) ? 0 : Request::input('lvl_4_on_off'));
            $column_preferences->lvl_5 = (is_null(Request::input('lvl_5_on_off')) ? 0 : Request::input('lvl_5_on_off'));
            $column_preferences->lvl_6 = (is_null(Request::input('lvl_6_on_off')) ? 0 : Request::input('lvl_6_on_off'));
            $column_preferences->save();
        }

        return Redirect::route('approval.paymentStatus')->with([
            'success' => Lang::get("Column preferences set.")
        ]);
    }

    public function columnPreferencesApprovalDashboard()
    {
        $data['column_preferences'] = new ColumnPreferencesApprovalDashboard();
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $data['column_preferences'] = $column_preferences_approval_dashboard;
        } else {
            $data['column_preferences']->user_id = Auth::user()->id;
            $data['column_preferences']->date = true;
            $data['column_preferences']->hospital = true;
            $data['column_preferences']->agreement = true;
            $data['column_preferences']->contract = true;
            $data['column_preferences']->practice = true;
            $data['column_preferences']->physician = true;
            $data['column_preferences']->log = true;
            $data['column_preferences']->details = true;
            $data['column_preferences']->duration = true;
            $data['column_preferences']->physician_approval = true;
            $data['column_preferences']->lvl_1 = true;
            $data['column_preferences']->lvl_2 = true;
            $data['column_preferences']->lvl_3 = true;
            $data['column_preferences']->lvl_4 = true;
            $data['column_preferences']->lvl_5 = true;
            $data['column_preferences']->lvl_6 = true;
            $data['column_preferences']->calculated_payment = false;
        }
        return View::make('approval/columnPreferencesApprovalDashboard')->with($data);
    }

    public function postColumnPreferencesApprovalDashboard()
    {
        $column_preferences_approval_dashboard = ColumnPreferencesApprovalDashboard::where('user_id', '=', Auth::user()->id)->first();
        if (!is_null($column_preferences_approval_dashboard)) {
            $column_preferences_approval_dashboard->date = (is_null(Request::input('date_on_off')) ? 0 : Request::input('date_on_off'));
            $column_preferences_approval_dashboard->hospital = (is_null(Request::input('hospital_on_off')) ? 0 : Request::input('hospital_on_off'));
            $column_preferences_approval_dashboard->agreement = (is_null(Request::input('agreement_on_off')) ? 0 : Request::input('agreement_on_off'));
            $column_preferences_approval_dashboard->contract = (is_null(Request::input('contract_on_off')) ? 0 : Request::input('contract_on_off'));
            $column_preferences_approval_dashboard->practice = (is_null(Request::input('practice_on_off')) ? 0 : Request::input('practice_on_off'));
            $column_preferences_approval_dashboard->physician = (is_null(Request::input('physician_on_off')) ? 0 : Request::input('physician_on_off'));
            $column_preferences_approval_dashboard->log = (is_null(Request::input('log_on_off')) ? 0 : Request::input('log_on_off'));
            $column_preferences_approval_dashboard->details = (is_null(Request::input('details_on_off')) ? 0 : Request::input('details_on_off'));
            $column_preferences_approval_dashboard->duration = (is_null(Request::input('duration_on_off')) ? 0 : Request::input('duration_on_off'));
            $column_preferences_approval_dashboard->physician_approval = (is_null(Request::input('physician_approval_on_off')) ? 0 : Request::input('physician_approval_on_off'));
            $column_preferences_approval_dashboard->lvl_1 = (is_null(Request::input('lvl_1_on_off')) ? 0 : Request::input('lvl_1_on_off'));
            $column_preferences_approval_dashboard->lvl_2 = (is_null(Request::input('lvl_2_on_off')) ? 0 : Request::input('lvl_2_on_off'));
            $column_preferences_approval_dashboard->lvl_3 = (is_null(Request::input('lvl_3_on_off')) ? 0 : Request::input('lvl_3_on_off'));
            $column_preferences_approval_dashboard->lvl_4 = (is_null(Request::input('lvl_4_on_off')) ? 0 : Request::input('lvl_4_on_off'));
            $column_preferences_approval_dashboard->lvl_5 = (is_null(Request::input('lvl_5_on_off')) ? 0 : Request::input('lvl_5_on_off'));
            $column_preferences_approval_dashboard->lvl_6 = (is_null(Request::input('lvl_6_on_off')) ? 0 : Request::input('lvl_6_on_off'));
            $column_preferences_approval_dashboard->calculated_payment = (is_null(Request::input('calculated_payment')) ? 0 : Request::input('calculated_payment'));
            $column_preferences_approval_dashboard->save();
        } else {
            $column_preferences = new ColumnPreferencesApprovalDashboard();
            $column_preferences->user_id = Auth::user()->id;
            $column_preferences->date = (is_null(Request::input('date_on_off')) ? 0 : Request::input('date_on_off'));
            $column_preferences->hospital = (is_null(Request::input('hospital_on_off')) ? 0 : Request::input('hospital_on_off'));
            $column_preferences->agreement = (is_null(Request::input('agreement_on_off')) ? 0 : Request::input('agreement_on_off'));
            $column_preferences->contract = (is_null(Request::input('contract_on_off')) ? 0 : Request::input('contract_on_off'));
            $column_preferences->practice = (is_null(Request::input('practice_on_off')) ? 0 : Request::input('practice_on_off'));
            $column_preferences->physician = (is_null(Request::input('physician_on_off')) ? 0 : Request::input('physician_on_off'));
            $column_preferences->log = (is_null(Request::input('log_on_off')) ? 0 : Request::input('log_on_off'));
            $column_preferences->details = (is_null(Request::input('details_on_off')) ? 0 : Request::input('details_on_off'));
            $column_preferences->duration = (is_null(Request::input('duration_on_off')) ? 0 : Request::input('duration_on_off'));
            $column_preferences->physician_approval = (is_null(Request::input('physician_approval_on_off')) ? 0 : Request::input('physician_approval_on_off'));
            $column_preferences->lvl_1 = (is_null(Request::input('lvl_1_on_off')) ? 0 : Request::input('lvl_1_on_off'));
            $column_preferences->lvl_2 = (is_null(Request::input('lvl_2_on_off')) ? 0 : Request::input('lvl_2_on_off'));
            $column_preferences->lvl_3 = (is_null(Request::input('lvl_3_on_off')) ? 0 : Request::input('lvl_3_on_off'));
            $column_preferences->lvl_4 = (is_null(Request::input('lvl_4_on_off')) ? 0 : Request::input('lvl_4_on_off'));
            $column_preferences->lvl_5 = (is_null(Request::input('lvl_5_on_off')) ? 0 : Request::input('lvl_5_on_off'));
            $column_preferences->lvl_6 = (is_null(Request::input('lvl_6_on_off')) ? 0 : Request::input('lvl_6_on_off'));
            $column_preferences->calculated_payment = (is_null(Request::input('calculated_payment')) ? 0 : Request::input('calculated_payment'));
            $column_preferences->save();
        }

        return Redirect::route('approval.index')->with([
            'success' => Lang::get("Column preferences set.")
        ]);
    }

    public function updatePendingPaymentCountForAllHospitals()
    {
        $data = HospitalContractSpendPaid::updatePendingPaymentCountForAllHospitals();
        return $data;
    }

    public function invoiceDashboardOnOff()
    {
        $data = Agreement::invoiceDashboardOnOff();
        return $data;
    }

    public function approverpendinglog($hospital_id)
    {
        $data = PhysicianLog::approverpendinglog($hospital_id);
        return $data;
    }

    public function paymentStatusReport()
    {
        $selected_manager = Request::input('export_manager_filter', 0);
        $payment_type = Request::input('export_payment_type', 0); // for payment type
        $contract_type = Request::input('export_contract_type', 0);
        $selected_hospital = Request::input('export_hospital', 0);
        $selected_agreement = Request::input('export_agreement', 0);
        $selected_practice = Request::input('export_practice', 0);
        $selected_physician = Request::input('export_physician', 0);
        $start_date = Request::input('export_start_date', '');
        $end_date = Request::input('export_end_date', '');
        $report_type = Request::input('export_report_type', 0);
        $status = Request::input('export_status', 0);
        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        $approver = $status == 4 ? Request::input("export_approver") : 0;

        $show_calculated_payment = false;
        PaymentStatusDashboardExportToExcelReport::dispatch(Auth::user()->id, $selected_manager, $payment_type, $contract_type, $selected_hospital, $selected_agreement, $selected_practice, $selected_physician, $start_date, $end_date, $report_type, $status, $show_calculated_payment, Auth::user()->group_id, $timestamp, $timeZone, $approver);

        return Redirect::route('approval.paymentStatus')
            ->with(['success' => Lang::get("Report generated successfully. We are sending payment status dashboard report to your email id in few minutes.")]);
    }


    private function sendNextLevelReminder($approvalManagerInfo)
    {
        if (count($approvalManagerInfo) > 0) {
            foreach ($approvalManagerInfo as $user_id => $manager_contracts) {
                $proxy_users = ProxyApprovalDetails::find_proxy_aaprover_users($user_id);
                foreach ($proxy_users as $proxy_user_id) {
                    $user = User::findOrFail($proxy_user_id);
                    $data["email"] = $user->email;
                    $data["name"] = $user->first_name;
                    $data['type'] = EmailSetup::APPROVAL_REMINDER_MAIL_NEXT_LEVEL;
                    $data['with'] = [
                        'name' => $user->first_name
                    ];

                    EmailQueueService::sendEmail($data);
                }
            }
        }
        return true;
    }
}
