<?php

namespace App\Http\Controllers;

use App\Console\Commands\OnCallScheduleCommand;
use App\Console\Commands\PracticeReportCommand;
use App\Console\Commands\HospitalReportMedicalDirectorshipContractCommand;
use App\Console\Commands\OnCallReportCommand;
use App\Group;
use App\PhysicianContracts;
use App\Http\Controllers\Validations\PhysicianValidation;
use App\Practice;
use App\PhysicianLog;
use App\PracticeType;
use App\State;
use App\InvoiceNote;
use App\User;
use App\Specialty;
use App\Physician;
use App\ContractType;
use App\Agreement;
use App\Contract;
use App\PaymentType;
use App\OnCallSchedules;
use App\Action;
use App\OnCallActivity;
use App\Hospital;
use App\PracticeManagerReport;
use App\Amount_paid;
use App\HospitalReport;
use App\PhysicianPractices;
use App\ContractDeadlineDays;
use App\PracticeReport;
use App\AccessToken;
use App\ActionHospital;
use App\customClasses\PaymentFrequencyFactoryClass;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\HospitalOverrideMandateDetails;
use App\HospitalTimeStampEntry;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\CustomCategoryActions;
use App\AttestationQuestion;
use App\Jobs\UpdatePaymentStatusDashboard;
use App\PhysicianType;
use App\Http\Controllers\Validations\EmailValidation;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\PracticeValidation;
use Session;
use stdClass;
use function App\Start\is_practice_manager;
use function App\Start\is_practice_owner;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\practice_report_path;


class PracticesController extends ResourceController
{
    const STATUS_FAILURE = 0;
    const STATUS_SUCCESS = 1;
    protected $requireAuth = true;

    public function getIndex()
    {

        $options = [
            'sort' => Request::input('sort', 4),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 4,
            'appends' => ['sort', 'order'],
            'field_names' => ['npi', 'name', 'state_id', 'created_at'],
            'per_page' => 9999
        ];

        $data = $this->query('Practice', $options, function ($query, $options) {
            $query->select('practices.*');

            switch ($this->currentUser->group_id) {
                case Group::HOSPITAL_ADMIN:
                case Group::SUPER_HOSPITAL_USER:
                case Group::HOSPITAL_CFO:
                    $query->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                        ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                        ->where('hospital_user.user_id', '=', $this->currentUser->id);
                    break;

                case Group::PRACTICE_MANAGER:
                    $query->join('practice_user', 'practice_user.practice_id', '=', 'practices.id')
                        ->where('practice_user.user_id', '=', $this->currentUser->id);
                    break;
            }

            return $query;
        });
        $data['table'] = View::make('practices/_practices')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/index')->with($data);
    }

    public function getShow($id)
    {
        $practice = Practice::findOrFail($id);
        if (!is_practice_owner($practice->id))
            App::abort(403);

        $recentLogs = PhysicianLog::select('physician_logs.*')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physician_logs.physician_id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->where('physician_practices.practice_id', '=', $practice->id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->where('physician_logs.practice_id', '=', $practice->id)
            ->where('contracts.end_date', '=', '0000-00-00 00:00:00')
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();


        // Issue fixes : physician count not match on hospital overview
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $physician_practices = PhysicianPractices::select("*")
            ->where("practice_id", "=", $id)
            ->whereRaw("start_date <= now()")
            ->whereRaw("end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->get();

        $data['practice_physicians_count'] = count($physician_practices);
        $data['practice'] = $practice;
        $data['recentLogs'] = $recentLogs;
        $data['table'] = View::make('practices/_recent_activity')->with($data)->render();

        return View::make('practices/show')->with($data);
    }

    public function getEdit($id)
    {
        $practice = Practice::findOrFail($id);
        $practiceTypes = options(PracticeType::all(), 'id', 'name');
        $states = options(State::all(), 'id', 'name');

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $managers = $practice->users()
            ->select(
                DB::raw("users.id as id"),
                DB::raw("concat(users.last_name, ', ', users.first_name) as name")
            )
            ->orderBy("last_name")
            ->orderBy("first_name")
            ->pluck('name', 'id');
        $primary_manager_id = $practice->hasPrimaryManager() ? $practice->getPrimaryManager()->id : -1;
        $invoice_notes = InvoiceNote::getInvoiceNotes($id, InvoiceNote::PRACTICE, $practice->hospital_id, 0);

        // $hospital = Hospital::findOrFail($practice->hospital_id);
        // $invoice_type = $hospital->invoice_type;

        $data = [
            "practice" => $practice,
            "practiceTypes" => $practiceTypes,
            "states" => State::orderBy('name')->pluck('name', 'id'),
            "managers" => $managers,
            "primary_manager_id" => $primary_manager_id,
            "note_count" => count($invoice_notes),
            "invoice_notes" => $invoice_notes,
            // "invoice_type" => $invoice_type
        ];

        return View::make('practices/edit')->with($data);
    }

    public function postEdit($id)
    {
        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $result = Practice::editPractice($id);
        return $result;
    }

    public function getDelete($id)
    {
        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        //drop column practice_id from table 'physicians' changes by 1254
        $practice = Practice::findOrFail($id);
        $physicianpractices = PhysicianPractices::where("practice_id", "=", $id)->pluck('physician_id')->toArray();

        foreach ($physicianpractices as $physicianpractice) {

            $physician = Physician::findOrFail($physicianpractice);
            if (!empty($physician)) {
                $checkPenddingLogs = PhysicianLog::penddingApprovalForPhysician($physician->id);
                if ($checkPenddingLogs) {
                    // log::info("if err");
                    return Redirect::back()->with([
                        'error' => Lang::get('practices.approval_pending_error')
                    ]);
                }
                $checkPenddingPayments = Amount_paid::penddingPaymentForPhysician($physician->id);
                if ($checkPenddingPayments) {
                    return Redirect::back()->with([
                        'error' => Lang::get('practices.payment_pending_error')
                    ]);
                }
            }
        }
        foreach ($physicianpractices as $physicianpractice) {
            $physician = Physician::findOrFail($physicianpractice);
            if (!empty($physician)) {
                $physician->apiContracts()->delete();
                $physician->logs()->delete();
                $physician->reports()->delete();
                $physician->accessTokens()->delete();
                $physician->delete();
            }
        }

        foreach ($practice->physicians as $physician) {
            // Below entire codde is modified for one to many changes by akash
            $physician->apiContracts()->where('contracts.practice_id', '=', $practice->id)->delete();
            $physician->logs()->where('physician_logs.practice_id', '=', $practice->id)->delete();
            $physician->reports()->where('physician_reports.practice_id', '=', $practice->id)->delete();
            $physician_practices = PhysicianPractices::where("physician_practices.physician_id", "=", $physician->id)
                ->where("physician_practices.practice_id", "=", $practice->id)
                ->delete();
            $physician_in_other_practices = PhysicianPractices::where("physician_practices.physician_id", "=", $physician->id)
                ->where("physician_practices.practice_id", "!=", $practice->id)
                ->get()->count();

            if ($physician_in_other_practices === 0) {
                $physician->accessTokens()->delete();
                $physician->delete();
            }
            // Akash changes ends

            /*** Below is a old code commented by akash for one to many support */
            // $physician->apiContracts()->delete();
            // $physician->logs()->delete();
            // $physician->reports()->delete();
            // $physician->accessTokens()->delete();
            // $physician->delete();
        }
        $practice->delete();

        return Redirect::route("practices.index")->with([
            "success" => Lang::get("practices.delete_success")
        ]);
    }

    public function getManagers($id)
    {
        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id)) {
            App::abort(403);
        }
        $hospital = Hospital::findOrFail($practice->hospital_id);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

        $data['practice'] = $practice;
        $data['hospital'] = $hospital;
        $data['table'] = View::make('practices/_users')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/managers')->with($data);
    }

    public function getAgreements($id)
    {
        $practice = Practice::findOrFail($id);
        $practiceTypes = options(PracticeType::all(), 'id', 'name');
        $states = options(State::all(), 'id', 'name');

        if (!is_super_user() && !is_super_hospital_user() && !is_practice_manager())
            App::abort(403);

        $managers = $practice->users()
            ->select(
                DB::raw("users.id as id"),
                DB::raw("concat(users.last_name, ', ', users.first_name) as name")
            )
            ->orderBy("last_name")
            ->orderBy("first_name")
            ->pluck('name', 'id');
        $primary_manager_id = $practice->hasPrimaryManager() ? $practice->getPrimaryManager()->id : -1;

        $data = [
            "practice" => $practice,
            "practiceTypes" => $practiceTypes,
            "states" => State::pluck('name', 'id'),
            "managers" => $managers,
            "primary_manager_id" => $primary_manager_id
        ];


        /*whereraw is_deleted clause added for soft delete of agreement on 12/04/2016*/
        $practice_agreements = DB::table('practices')->select(DB::raw("distinct(agreements.id),agreements.name, agreements.start_date,agreements.end_date"))

            //drop column practice_id from table 'physicians' changes by 1254
            //->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
            //->join("agreements", "agreements.hospital_id", "=", "hospitals.id")
            ->whereRaw("agreements.archived = 0")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("contracts.archived = 0")
            ->where('practices.id', '=', $id)
            ->get();
        $data['agreements'] = $practice_agreements;
        // echo json_encode($data['agreements']);

        if (Request::ajax()) {
            return Response::json($data);
        }
        return View::make('practices/agreements')->with($data);
    }

    public function showPracticeContracts($practiceId, $contractId)
    {
        $practice = Practice::findOrFail($practiceId);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

        $practice = new Practice();
        $data = $practice->getPracticeContracts($practiceId, $contractId);
        //physician to multiple hospital by 1254
        $data['practiceId'] = $practiceId;

        if (Request::ajax()) {
            return Response::json($data);
        }
        return View::make('practices/show_on_call_schedule')->with($data);
    }

    public function getPracticeContracts($id)
    {
        $practice = Practice::findOrFail($id);
        $practiceTypes = options(PracticeType::all(), 'id', 'name');
        $states = options(State::all(), 'id', 'name');

        if (!is_super_user() && !is_super_hospital_user() && !is_practice_manager())
            App::abort(403);

        $managers = $practice->users()
            ->select(
                DB::raw("users.id as id"),
                DB::raw("concat(users.last_name, ', ', users.first_name) as name")
            )
            ->orderBy("last_name")
            ->orderBy("first_name")
            ->pluck('name', 'id');
        $primary_manager_id = $practice->hasPrimaryManager() ? $practice->getPrimaryManager()->id : -1;

        $data = [
            "practice" => $practice,
            "practiceTypes" => $practiceTypes,
            "states" => State::pluck('name', 'id'),
            "managers" => $managers,
            "primary_manager_id" => $primary_manager_id
        ];

        /*below whereRaw clause of is_deleted is added for soft delete i.e. if agreement is deleted then practice off that agreement will not be shown
            Code modified_on: 07/04/2016
            */

        $practice_agreements = DB::table('practices')->select(DB::raw("distinct(agreements.id),agreements.name, agreements.start_date,agreements.end_date"))
            // ->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
//            ->join("contracts", "contracts.physician_id", "=", "physician_practices.physician_id")
            ->join('physician_contracts', function ($join) {
                $join->on('physician_contracts.physician_id', '=', 'physician_practices.physician_id');
                $join->on('physician_contracts.practice_id', '=', 'physician_practices.practice_id');

            })
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
            ->join("agreements", function ($join) {
                $join->on("contracts.agreement_id", "=", "agreements.id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->whereRaw("agreements.archived = 0")
            ->whereRaw('contracts.end_date = "0000-00-00 00:00:00"')
            ->whereRaw("agreements.is_deleted = 0")
            //->whereRaw("contracts.archived = 0")
            ->where('practices.id', '=', $id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull('contracts.deleted_at')
            ->get();

        $data['agreements'] = $practice_agreements;
        $contractArray = array();
        foreach ($data['agreements'] as $agreement) {
            $agreement = Agreement::findOrFail($agreement->id);
            $data['contracts'] = $this->getContractsForAgreement($agreement, $id);

            foreach ($data['contracts'] as $contract) {
                $physician_names_str = "";
                $practice_present = 0;
                $share_contracts = PhysicianContracts::where('contract_id', '=', $contract->id)->groupBy('physician_id')->get();
                if(count($share_contracts) > 1){
                    foreach ($contract->practices as $practice) {
                        if ($practice->id == $id) {
                            $practice_present++;
                            $physician_names_str = "";
                            foreach ($practice->physicians as $key => $physician) {
                                $physician_count = PhysicianContracts::where('contract_id', '=', $physician->contract_id)->count();
                                if($physician_count > 1){
                                    if ($key == 0) {
                                        $physician_names_str .= str_replace(',', '', $physician->name) . ', ';
                                    } else if ($key == count($practice->physicians) - 1) {
                                        $physician_names_str .= str_replace(',', '', $physician->name);
                                    } else {
                                        $physician_names_str .= str_replace(',', '', $physician->name) . ',';
                                    }
                                }
                            }
                        }
                    }
                }else{
                    foreach($contract->practices as $practice){
                        if($practice->id == $id){
                            $practice_present++;
                        }
                    }
                    foreach($share_contracts as $share_contract){
                        $physician_names_str = "";
                        $physician = Physician::findOrFail($share_contract->physician_id);
                        $physician_names_str = $physician->first_name." ".$physician->last_name;
                    }
                }
                
                if ($practice_present > 0) {
                    $singleContract = Contract::findOrFail($contract->id);
//                    $physician = Physician::findOrFail($singleContract->physician_id);
                    $singleAgreement = Agreement::findOrFail($singleContract->agreement_id);
                    //if(strtotime($singleContract['manual_contract_end_date'])>strtotime(date('m/d/Y'))){
                    $today = date('Y-m-d');
                    $valid_upto = $singleContract->manual_contract_valid_upto;
                    if ($valid_upto == '0000-00-00') {
                        //$valid_upto=$contract->agreement_end_date; /* remove to add valid upto to contract*/
                        $valid_upto = $singleContract->manual_contract_end_date;
                    }
                    if ($valid_upto > $today) {
                        $contractArray[] = [
                            'contract_id' => $contract->id,
                            'contract_name' => $contract->name,
                            'contract_type_id' => $contract->contract_type_id,
                            'payment_type_id' => $contract->payment_type_id,
                            'agreement_start_date' => date('m/d/Y', strtotime($singleAgreement->start_date)),
                            'agreement_end_date' => date('m/d/Y', strtotime($singleAgreement->end_date)),
                            'agreement_id' => $singleAgreement->id,
                            'physican_id' => $physician_names_str,
                        ];
                        //}
                    }
                }
            }
        }

        $data['contractArray'] = $contractArray;
        if (Request::ajax()) {
            return Response::json($data);
        }
        return View::make('practices/contracts')->with($data);
    }

    private function getContractsForAgreement($agreement, $practice_id = 0)
    {
        $data = [];
        //log::info("practice_id",array($practice_id));

        if ($practice_id != 0) {
            $contracts = Contract::select('contracts.*')
                ->join('sorting_contract_names', 'sorting_contract_names.contract_id', '=', 'contracts.id')  // 6.1.13
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                ->where('physician_contracts.practice_id', "=", $practice_id)
                ->whereNull('physician_contracts.deleted_at')
                ->where('sorting_contract_names.is_active', '=', 1)     // Sprint 6.1.13
                ->orderBy("sorting_contract_names.sort_order", "ASC")   // Sprint 6.1.13
                ->distinct('contracts.id')
                ->get();
        } else {
            $contracts = Contract::select('contracts.*')
                ->join('sorting_contract_names', 'sorting_contract_names.contract_id', '=', 'contracts.id')  // 6.1.13
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                ->whereNull('physician_contracts.deleted_at')
                ->where('sorting_contract_names.is_active', '=', 1)               // Sprint 6.1.13
                ->orderBy("contracts.sorting_contract_names.sort_order", "ASC")   // Sprint 6.1.13
                ->groupBy('contract_name_id')
                ->groupBy('contract_type_id')
                ->distinct('contracts.id')
                ->get();

        }

        foreach ($contracts as $contract) {
            $contract_data = new StdClass();
            $contract_data->id = $contract['id'];
            $contract_data->name = contract_name($contract);
            $contract_data->practices = [];
            $contract_data->contract_type_id = $contract->contract_type_id;
            $contract_data->payment_type_id = $contract->payment_type_id;
            //issue 6 : physician to multiple hospital by 1245 : 17022021
            $practices = Practice::select('practices.*')
                // ->join('physicians', 'physicians.practice_id', '=', 'practices.id')
                ->join('physician_practices', 'physician_practices.practice_id', '=', 'practices.id')
                ->join('physician_contracts', function ($join) {
                    $join->on('physician_contracts.physician_id', '=', 'physician_practices.physician_id');
                    $join->on('physician_contracts.practice_id', '=', 'physician_practices.practice_id');

                })
                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                ->where('contracts.payment_type_id', '=', $contract->payment_type_id)
                ->where('physician_practices.hospital_id', '=', $agreement->hospital_id)
//               ->where('physician_practices.physician_id','=',$contract->physician_id)
                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                ->whereNull('physician_practices.deleted_at')
                ->whereNull('physician_contracts.deleted_at')
                ->groupBy('practices.id')
                ->orderBy('practices.name')
                ->distinct('practices.id')
                ->get();
            foreach ($practices as $index => $practice) {
                $practice_data = new StdClass();
                $practice_data->id = $practice->id;
                $practice_data->name = $practice->name;
                $practice_data->physicians = [];
                $practice_data->first = $index == 0;

                $physicians = Physician::select(
                    DB::raw("physicians.*"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.mandate_details as mandate_details"))
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('physician_contracts', function ($join) {
                        $join->on('physician_contracts.physician_id', '=', 'physician_practices.physician_id');
                        $join->on('physician_contracts.practice_id', '=', 'physician_practices.practice_id');

                    })
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                    ->where('contracts.agreement_id', '=', $agreement->id)
                    ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                    ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                    ->where('contracts.payment_type_id', '=', $contract->payment_type_id)
                    ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                    // ->where('physicians.practice_id', '=', $practice->id)
                    ->where('physician_practices.practice_id', '=', $practice->id)
                    ->whereNull('physician_contracts.deleted_at')
                    ->orderBy('physicians.first_name')
                    ->orderBy('physicians.last_name')
                    ->get();

                foreach ($physicians as $index => $physician) {
                    $physician_data = new StdClass();
                    $physician_data->id = $physician->id;
                    $physician_data->contract_id = $physician->contract_id;
                    $physician_data->mandate_details = $physician->mandate_details;
                    $physician_data->name = "{$physician->last_name}, {$physician->first_name}";
                    $physician_data->first = $index == 0;

                    $practice_data->physicians[] = $physician_data;
                }

                $contract_data->practices[] = $practice_data;
            }

            $data[] = $contract_data;
        }

        return $data;
    }

    public function showPracticeAgreements($id, $agreement_id)
    {
        $agreement = Agreement::findOrFail($agreement_id);
        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id)) {
            App::abort(403);
        }

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

        $data['practice'] = $practice;
        $data['contracts'] = $this->getContractsForAgreement($agreement, $practice->id);

        $data['table'] = View::make('agreements/_contracts')->with($data)->render();
        $data['remaining'] = $this->getRemainingDays($agreement);
        if ($data['remaining'] < 0)
            $data['remaining'] = 0;
        $data['agreement'] = $agreement;

        $data['dates'] = Agreement::getAgreementData($agreement);
        foreach ($data['contracts'] as $contracts) {
            $data['contract_type_id'] = $contracts->contract_type_id;
            $data['payment_type_id'] = $contracts->payment_type_id;
            break;
        }
        return View::make('practices/show_agreement')->with($data);
    }

    private function getRemainingDays($agreement)
    {
        return days('now', $agreement->end_date);
    }

    public function getOnCallScheduleData($contractId, $date_index)
    {
        $getAgreementID = DB::table('contracts')
            ->where('id', '=', $contractId)
            ->pluck('agreement_id');
        $agreement = Agreement::findOrFail($getAgreementID);
        $data['dates'] = Agreement::getAgreementData($agreement);
        $selected_date = explode(": ", $data['dates']->start_dates[$date_index]);
        $start_date = mysql_date($selected_date[1]);
        $end_date = date("Y-m-t", strtotime($start_date));;

        for ($i = strtotime($start_date); $i <= strtotime($end_date); $i += 86400) {
            $day = strftime("%a", strtotime(date('m/d/Y', $i)));
            $list[] = $day . " " . date('m/d/Y', $i);
        }

        $data['start_date_list'] = $list;
        $data['contract_type_id'] = ContractType::ON_CALL;
        $data['payment_type_id'] = PaymentType::PER_DIEM;
        $data['fetch_oncall_data'] = DB::table('on_call_schedule')
            ->select('physician_id', 'physician_type', 'date')
            ->where("agreement_id", "=", $getAgreementID)
            ->whereBetween("date", [$start_date, $end_date])
            ->orderBy("date", "asc")
            ->get();
        $physiciansArray = array();
        $physiciansDateArray = array();
        foreach ($data['fetch_oncall_data'] as $fetchedData) {
            $date = $fetchedData->date;

            $am_physician = DB::table('physicians')
                ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                ->where('id', '=', $fetchedData->physician_id)->first();
            if ($am_physician) {
                $am = $am_physician->full_name;
                $physiciansArray[] = [
                    "physician_id" => $am,
                    "physician_type" => $fetchedData->physician_type,
                    "date" => $date
                ];
            }
        }
        $physiciansDateArray = array();
        $physiciansArray = array();
        foreach ($data['fetch_oncall_data'] as $fetchedData) {
            $date = $fetchedData->date;
            $physician = DB::table('physicians')
                ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                ->where('id', '=', $fetchedData->physician_id)->first();
            if ($physician) {
                $physician_fullname = $physician->full_name;
                if (array_key_exists($date, $physiciansArray)) {

                    $scheduleRow = $physiciansArray[$date];

                    if ($fetchedData->physician_type == 1) {
                        $scheduleRow['am'][] = $physician_fullname;
                    }
                    if ($fetchedData->physician_type == 2) {
                        $scheduleRow['pm'][] = $physician_fullname;
                    }
                    $physiciansArray[$date] = $scheduleRow;
                } else {
                    $scheduleRow = array();
                    $scheduleRow['date'] = $date;
                    if ($fetchedData->physician_type == 1) {

                        $scheduleRow['am'][] = $physician_fullname;
                    }
                    if ($fetchedData->physician_type == 2) {
                        $scheduleRow['pm'][] = $physician_fullname;
                    }
                    $physiciansArray[$date] = $scheduleRow;
                }
            }
        }

        $data['physicians_data'] = $physiciansArray;
        $data['table'] = View::make('practices/view_schedule_dates')->with($data)->render();
        return $data;
    }

    public function scheduling($id, $agreement_id)
    {
        $practice = Practice::findOrFail($id);
        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];
        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

        $data['practice'] = $practice;
        $agreement = Agreement::findOrFail($agreement_id);

        $data['hospital'] = $agreement->hospital;

        $contracts = $this->getContractsForAgreement($agreement, $practice->id);
        $physicians_array = array();
        foreach ($contracts as $contract) {
            $contract_name = $contract->name;
            foreach ($contract->practices as $practice) {
                foreach ($practice->physicians as $physicians) {
                    $physicians_array[] = [
                        "id" => $physicians->id,
                        "name" => $physicians->name
                    ];
                }
            }
        }
        $data['physicians'] = $physicians_array;
        $data['contract_name'] = $contract_name;

        $data['agreement'] = $agreement;
        $data['expiring'] = $this->isAgreementExpiring($agreement);
        $data['expired'] = $this->isAgreementExpired($agreement);
        if ($agreement->archived) {
            $data['expired'] = false;
        }
        $data['remaining'] = $this->getRemainingDays($agreement);

        $data['contracts'] = $this->getContractsForAgreement($agreement, $practice->id);
        $data['dates'] = Agreement::getAgreementData($agreement);
        foreach ($data['dates']->dates as $date) {
            $date_array[] = $date;
        }
        $start_date_array[] = $data['dates']->start_dates;
        $data['date_array'] = $date_array;
        $data['start_date_array'] = $start_date_array;
        $date = $data['dates']->start_date;
        $date_parse = date_parse_from_format("m/d/Y", $date);
        $month = $date_parse["month"];
        $year = $date_parse["year"];
        $start_date = "01-" . $month . "-" . $year;
        $start_time = strtotime($start_date);
        $end_time = strtotime("+1 month", $start_time);

        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $list[] = date('m/d/Y', $i);
        }
        $data['pluck'] = $list;
        if ($data['remaining'] < 0)
            $data['remaining'] = 0;

        return View::make('practices/on_call')->with($data);
    }

    private function isAgreementExpiring($agreement)
    {
        return $this->getRemainingDays($agreement) <= 15;
    }

    private function isAgreementExpired($agreement)
    {
        return $this->getRemainingDays($agreement) < 0;
    }

    public function getDataOnCall($id, $agreement_id, $date_index)
    {
        $date_practice = explode('~', $date_index);
        $date_index = $date_practice[0];
        $practice_id = $date_practice[1];
        $agreement = Agreement::findOrFail($agreement_id);
        $hospital = $agreement->hospital;

        $data['hospital'] = $hospital;
        $data['agreement'] = $agreement;
        $data['expiring'] = $this->isAgreementExpiring($agreement);
        $data['expired'] = $this->isAgreementExpired($agreement);
        if ($agreement->archived) {
            $data['expired'] = false;
        }
        $data['remaining'] = $this->getRemainingDays($agreement);

        $data['contracts'] = $this->getContractsForAgreement($agreement, $practice_id);

        $data['dates'] = Agreement::getAgreementData($agreement);
        $selected_date = explode(": ", $data['dates']->start_dates[$date_index]);
        $date = mysql_date($selected_date[1]);
        $data['fetch_oncall_data'] = DB::table('on_call_schedule')->select('practice_id', 'physician_id',
            'physician_type', 'date')
            ->where("on_call_schedule.agreement_id", "=", $agreement_id)
            ->get();

        $start_time = strtotime($selected_date[1]);
        $end_time = strtotime("+1 month", $start_time);
        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $day = strftime("%a", strtotime(date('m/d/Y', $i)));
            $list[] = $day . " " . date('m/d/Y', $i);
        }

        $data['start_date_list'] = $list;
        $physicians_array = array();
        foreach ($data['contracts'] as $contractss) {
            foreach ($contractss->practices as $practice) {
                foreach ($practice->physicians as $physicians) {
                    $physicians_array[] = [
                        "id" => $physicians->id,
                        "name" => $physicians->name
                    ];
                }
            }
        }
        $data['all_physicians'] = $physicians_array;
        if (is_practice_manager()) {
            $data['table'] = View::make('practices/dynamic_dates_view')->with($data)->render();
        } else {
            $data['table'] = View::make('practices/dynamic_dates')->with($data)->render();
        }
        return $data;
    }

    public function postSaveOnCall($id, $agreement_id)
    {
        $agreement = Agreement::findOrFail($agreement_id);

        $data['contracts'] = $this->getContractsForAgreement($agreement, $practice_id = 0);

        foreach ($data['contracts'] as $cn) {
            $contract_name = $cn->name;
        }
        $data['dates'] = Agreement::getAgreementData($agreement);
        $pm_phisicians = Request::all();
        $remove = array(0);
        $phy_arr = array();
        $result = array_diff($pm_phisicians, $remove);
        $temp_date_array = $data['dates']->start_dates[$result['select_date']];
        $temp_date = explode(':', $temp_date_array);
        $date = $temp_date[1];
        $end_time = strtotime("+1 month", strtotime($date));
        $report_start_date = ($date);
        $report_end_date = (date("m/t/Y", strtotime($date)));
        $date_arr = explode('/', $date);
        $month = $date_arr[0];
        $day = $date_arr[1];
        $year = $date_arr[2];

        $practice_id = $result['practice_id'];
        /*
         * "am_phisicians_" and "pm_phisicians_"
         * are the name of resp select boxes */
        if (array_key_exists('date_count', $result)) {
            for ($i = 0; $i < $result['date_count']; $i++) {
                $j = $i;
                $k = $j + 1;
                if (array_key_exists('am_phisicians_' . $i, $result)) {
                    $phy_arr[] = [
                        "physician_id" => $result['am_phisicians_' . $i],
                        "physician_type" => 1,
                        "date" => mysql_date($month . "/" . $k . "/" . $year)
                    ];
                }
                if (array_key_exists('pm_phisicians_' . $i, $result)) {
                    $phy_arr[] = [
                        "physician_id" => $result['pm_phisicians_' . $i],
                        "physician_type" => 2,
                        "date" => mysql_date($month . "/" . $k . "/" . $year)
                    ];
                }
            }
        }
        if (array_key_exists('save', $pm_phisicians)) {
            if (count($phy_arr) > 0) {
                foreach ($phy_arr as $array) {
                    $check_data = DB::table('on_call_schedule')->select('agreement_id')
                        ->where("agreement_id", "=", $agreement_id)
                        ->where("physician_type", "=", $array['physician_type'])
                        ->where("date", "=", mysql_date($array['date']))
                        ->count();

                    if ($check_data == 0) {
                        $oncall = new OnCallSchedules();
                        $oncall->agreement_id = $agreement_id;
                        $oncall->physician_id = $array['physician_id'];
                        $oncall->physician_type = $array['physician_type'];
                        $oncall->date = mysql_date($array['date']);
                        $oncall->save();
                    } else {
                        $update_data = DB::table('on_call_schedule')
                            ->where("agreement_id", "=", $agreement_id)
                            ->where("date", "=", mysql_date($array['date']))
                            ->where("physician_type", "=", $array['physician_type'])
                            ->update(array('physician_id' => $array['physician_id']));
                    }
                }
                if ($check_data == 0) {
                    if ($oncall->save()) {
                        return Redirect::back()
                            ->with(['success' => Lang::get('practices.schedule_insert_success'),
                                'date_select_index' => $result['select_date']
                            ])
                            ->withInput();
                    }
                } else {
                    return Redirect::back()
                        ->with(['success' => Lang::get('practices.schedule_update_success'),
                            'date_select_index' => $result['select_date']
                        ])
                        ->withInput();
                }
            }
            return Redirect::back()
                ->with(['error' => Lang::get('practices.select_one'),
                    'date_select_index' => $result['select_date']
                ])
                ->withInput();
        }
        if (array_key_exists('export', $pm_phisicians)) {

            $hospital = Hospital::findOrFail(45);
            /*if (!is_hospital_owner($hospital->id))
                App::abort(403);*/
            Artisan::call('reports:oncallschedule', [
                'hospital' => $hospital->id,
                'report_start_date' => $report_start_date,
                'report_end_date' => $report_end_date,
                'contract_name' => $contract_name,
                'agreement_id' => $agreement_id
            ]);
            if (!OnCallScheduleCommand::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(OnCallScheduleCommand::$message)
                ]);
            }
            return Redirect::back()->with([
                'success' => Lang::get(OnCallScheduleCommand::$message),
                'report_id' => OnCallScheduleCommand::$report_id,
                'report_filename' => OnCallScheduleCommand::$report_filename,
                'hospital_id' => $hospital->id,
                'date_select_index' => $result['select_date']
            ]);
        }
    }

    public function deleteLog($log_id)
    {
        try {
            PhysicianLog::where('id', "=", $log_id)->delete();
            return Redirect::back()
                ->with(['success' => Lang::get('practices.log_delete_success')
                ]);
        } catch (Exception $e) {
            return Redirect::back()
                ->with(['error' => Lang::get('practices.log_delete_unsuccess')
                ]);
        }
    }

    public function getShowOnCallEntry($practiceId, $contractId)
    {
        /*
         * Steps for the page
         * page contains 3 parts
         *      1. Calendar for on call entry
         *      2. Recent Logs
         *      3. Log Approval panel
         */

        $practice = new Practice();
        $data = $practice->getPracticeContracts($practiceId, $contractId);

        $today = date('Y-m-d');
        $lvl1_counter = 0;
        $level1 = $data['physicians'];
        foreach ($data['physicians'] as $physician_lvl1) {
            if ($physician_lvl1['valid_upto'] <= $today) {
                unset($level1[$lvl1_counter]);
            }
            $lvl1_counter++;
        }

        $physician_list = [];
        $selected_physician_id = 0;
        $selected_contract_id = 0;
        foreach($level1 as $level1Obj) {
            if($level1Obj['contract'] == $contractId){
                $physician_list[] = $level1Obj;
                $selected_physician_id = $level1Obj['id'];
                $selected_contract_id = $level1Obj['contract'];
                foreach($level1 as $level1Obj) {
                    if($level1Obj['id'] != $selected_physician_id){
                        $physician_list[] = $level1Obj;
                    }
                }
            }
        }
        $level1 = array_values($physician_list);
        $data['physicians'] = $level1;

        foreach ($data['contracts'] as $contract_lvl) {
            foreach ($contract_lvl->practices as $practice_lvl) {
                $lvl2_counter = 0;
                $level2 = $practice_lvl->physicians;
                foreach ($practice_lvl->physicians as $physician_lvl2) {
                    if ($physician_lvl2->valid_upto <= $today) {
                        unset($level2[$lvl2_counter]);
                    }
                    $lvl2_counter++;
                }
                $level2 = array_values($level2);
                $practice_lvl->physicians = $level2;
            }
        }

        //call-coverage-duration  by 1254 : send partial hours of contract
        $contract = Contract::findOrFail($contractId);
        $data["contract"] = $contract;
        //$data = [];
        return View::make('practices/on_call_entry')->with($data);
    }

    public function getShowPhysicianLogEntry($practiceId, $contractId)
    {
        /*
         * Steps for the page
         * page contains 3 parts
         *      1. Calendar for on call entry
         *      2. Recent Logs
         *      3. Log Approval panel
         */

        $practice = Practice::findOrFail($practiceId);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

        $practice = new Practice();
        $data = $practice->getPracticeContracts($practiceId, $contractId);

        //$data = [];
        return View::make('practices/physician_log_entry')->with($data);
    }

    public function getShowPhysicianPsaWrvuLogEntry($practiceId, $contractId)
    {
        /*
         * Steps for the page
         * page contains 3 parts
         *      1. Calendar for on call entry
         *      2. Recent Logs
         *      3. Log Approval panel
         */

        /*$contract = Contract::findOrFail($practiceId);
        echo json_encode($contract);
        $recentLogs = $this->getRecentLogs($contract);

        echo json_encode($recentLogs);*/

        $practice = Practice::findOrFail($practiceId);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });

//        $data['practice'] = $practice;
//
//        $getAgreementID = DB::table('contracts')
//            ->where('id', '=', $contractId)
//            ->pluck('agreement_id');
//        $agreement = Agreement::findOrFail($getAgreementID);
//
//        $data['contracts'] = $this->getContractsForAgreement($agreement);
//        $physicians_array=array();
//        foreach( $data['contracts'] as $contracts)
//        {
//            if($contractId==$contracts->id){
//                $contract_name=$contracts->name;
//            }
//            foreach($contracts->practices as $practice){
//                if($practice->id == $practiceId) {
//                    foreach ($practice->physicians as $physicians) {
//                        if ($contracts->id == $contractId) {
//                            $contractNameId = ContractName::where("name", "=", $contracts->name)->firstOrFail();
//                            $contractForPhysician = DB::table('contracts')
//                                ->where('physician_id', '=', $physicians->id)
//                                ->where('agreement_id', '=', $getAgreementID)
//                                ->where('contract_name_id', '=', $contractNameId->id)->get();
//                            $physicians_array[] = [
//                                "id" => $physicians->id,
//                                "name" => $physicians->name,
//                                "contract" => $contractForPhysician[0]->id,
//                                "mandate_details" => $physicians->mandate_details,
//                                "contractName" => $contracts->name
//                            ];
//                        }
//                    }
//                }
//            }
//        }
//        $data['contract_name'] = $contract_name;
//        $data['physicians'] = $physicians_array;
//        $data['activities'] = [];
//        $data['dates'] = Agreement::getAgreementData($agreement);
//
//        $data['start_date']=$data['dates']->start_date;
//        $data['practice_id'] = $practiceId;
//        $data['contract_id'] = $contractId;
//        $data['agreement_id'] = $getAgreementID;
//
//        //Currently we implemented this functionality only for on call contracts
//        $data['send_contract_type_id'] = ContractType::ON_CALL;
        $practice = new Practice();
        $data = $practice->getPracticeContracts($practiceId, $contractId);
        $contract = Contract::where('id', '=', $data['physicians'][0]['contract'])->first();
        $data['contract'] = $contract;
        $periods = $data['dates']->dates;
        $data['dates']->dates = [];

        foreach ($periods as $key => $value) {
            if ($key <= $data['dates']->current_month) {
                $data['dates']->dates[$key] = $value;
            }
        }

        //$data = [];
        return View::make('practices/physician_psa_wrvu_log_entry')->with($data);
    }

    public function postPracticeSaveLog()
    {
        $action_id = Request::input("action");
        $shift = Request::input("shift");
        $duration = Request::input("duration");
        $log_details = Request::input("notes");
        $physician_id = Request::input("physicianId");
        $physician = Physician::findOrFail($physician_id);
        $userData = Auth::user();
        $user_id = $userData->id;
        $start_time = Request::input("start_time", "");
        $end_time = Request::input("end_time", "");

        if (Request::input("zoneName") != '') {
            if (!strtotime(Request::input("current"))) {
                $zone = new DateTime(strtotime(Request::input("current")));
            } else {
                $zone = new DateTime(false);
            }
            //$zone->setTimezone(new DateTimeZone('Pacific/Chatham'));
            $zone->setTimezone(new DateTimeZone(Request::input("zoneName")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('T'));
            Request::merge(['timeZone' => Request::input("timeStamp") . ' ' . $zone->format('T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }

        $contract_id = Request::input("contractId");
        $contract = Contract::findOrFail($contract_id);

        /* TODO */
        // if (Request::input("action") === "" || Request::input("action") == null || Request::input("action") == -1) {
        if (Request::input("action") === "" || Request::input("action") == null) {
            return "both_actions_empty_error";
        }
        if (Request::input("action") == -1) {
            $action = new Action;
            $action->name = Request::input("action_name");
            $action->contract_type_id = $contract->contract_type_id;
            $action->payment_type_id = $contract->payment_type_id;
            $action->action_type_id = 5;
            $action->save();
            $action_id = $action->id;
        }
        $getAgreementID = DB::table('contracts')
            ->where('id', '=', $contract_id)
            ->value('agreement_id');
        $agreement = Agreement::findOrFail($getAgreementID);
        $start_date = $agreement->start_date;
        $end_date = $agreement->end_date;
        $selected_dates = Request::input("dates");
        foreach ($selected_dates as $selected_date) {
            $user = PhysicianLog::where('physician_id', '=', $physician_id)
                ->where('date', '=', mysql_date($selected_date))
                ->where('contract_id', '=', $contract_id)
                ->first();
            //check for contract dealine option & contract deadline days
            if ((mysql_date($selected_date) >= $start_date) && (mysql_date($selected_date) <= $end_date)) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                } else {
                    $contract_Deadline_number_string = '-365 days';
                }


                if (strtotime(mysql_date($selected_date)) > strtotime($contract_Deadline_number_string)) {
                    //Check hours log is under 24 and for directership it also under annual cap
                    $physician_log = new PhysicianLog();
                    $checkHours = $physician_log->getHoursCheck($contract_id, $physician_id, $selected_date, $duration);
                    if ($checkHours === 'Under 24' || $contract->payment_type_id === 4) {
                        if (($duration < 0.25 || !is_numeric($duration)) && ($start_time == "" && $end_time == "") && ($contract->payment_type_id != PaymentType::TIME_STUDY)) {
                            return "no_duration";
                        } else {
                            return $physician_log->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
                        }
                    } else {
                        return $checkHours;
                    }
                } else {
                    return "Excess 365";
                }
            }
        }
    }

    public function sendPhysicianApprovalEmail()
    {
        $physician = Request::input("physician");
        $physician_info = Physician::findOrFail($physician);
        $userData = Auth::user();
        $data = [
            "email" => $physician_info->email,
            "name" => $physician_info->first_name . ' ' . $physician_info->last_name,
            "requested_by_email" => $userData->email,
            "requested_by" => $userData->first_name . ' ' . $userData->last_name,
            'type' => EmailSetup::LOG_APPROVAL_FROM_PM,
            'with' => [
                'name' => $physician_info->first_name . ' ' . $physician_info->last_name,
                'requested_by' => $userData->first_name . ' ' . $userData->last_name,
                'requested_by_email' => $userData->email
            ],
            'subject_param' => [
                'name' => '',
                'date' => '',
                'month' => '',
                'year' => '',
                'requested_by' => $userData->first_name . ' ' . $userData->last_name,
                'manager' => '',
                'subjects' => ''
            ]
        ];
        try {
            EmailQueueService::sendEmail($data);
            return "sent";
        } catch (Exception $e) {
            return "not sent";
        }
    }

    public function submitLogForMultipleDates()
    {
        $userData = Auth::user();
        $user_id = $userData->id;
        $action_id = Request::input("action");
        $shift = Request::input("shift");
        $duration = 0;
        $start_time = Request::input("start_time", "");
        $end_time = Request::input("end_time", "");

        if (Request::input("zoneName") != '') {
            if (!strtotime(Request::input("current"))) {
                $zone = new DateTime(strtotime(Request::input("current")));
            } else {
                $zone = new DateTime(false);
            }
            //$zone->setTimezone(new DateTimeZone('Pacific/Chatham'));
            $zone->setTimezone(new DateTimeZone(Request::input("zoneName")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('T'));
            Request::merge(['timeZone' => Request::input("timeStamp") . ' ' . $zone->format('T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }

        $log_details = Request::input("notes");
        $physician_id = Request::input("physicianId");
        $physician = Physician::findOrFail($physician_id);

        $contract_id = Request::input("contractId");
        $contract = Contract::findOrFail($contract_id);

        if (Request::input("action") != "") {
            // $agreement_id=
            $getAgreementID = DB::table('contracts')
                ->where('id', '=', $contract_id)
                ->pluck('agreement_id');
            $agreement = Agreement::findOrFail($getAgreementID);
            //Newly added line
            $agreement = $agreement->first();

            $start_date = $agreement->start_date;
            $end_date = $agreement->end_date;


            $hours = DB::table("action_contract")
                ->select("hours")
                ->where("contract_id", "=", $contract_id)
                ->where("action_id", "=", Request::input("action"))
                ->first();

            if ($hours != "")
                $duration = $hours->hours;
            else
                $duration = 0;

            if ($contract->partial_hours == 1) {
                $duration = Request::input("duration");
            }

            $selected_dates = Request::input("dates");
            //$selected_dates_array = explode(',', $selected_dates);

            $log_error_for_dates = [];
            foreach ($selected_dates as $selected_date) {
                $user = PhysicianLog::where('physician_id', '=', $physician_id)
                    ->where('date', '=', mysql_date($selected_date))
                    ->where('contract_id', '=', $contract_id)
                    ->first();
                /*if (count($user) > 0) {
                    DB::table('physician_logs')
                        ->where('physician_id', '=', $physician_id)
                        ->where('date', '=', mysql_date($selected_date))
                        ->where('contract_id', '=', $contract_id)
                        ->update(array(
                            'duration' => $duration,
                            'action_id' => $action_id,
                            'details' => $log_details
                        ));
                } else {*/

                if ((mysql_date($selected_date) >= $start_date) && (mysql_date($selected_date) <= $end_date)) {
                    if ($contract->deadline_option == '1') {
                        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . 'days';
                    } else {
                        $contract_Deadline_number_string = '-90 days';
                    }
                    if (strtotime($selected_date) > strtotime($contract_Deadline_number_string)) {
                        //some server side validation
                        //fetch already entered logs for the physician, same contract, same date

                        $physician_logs = new PhysicianLog();
                        $results = $physician_logs->validateLogEntryForSelectedDate($physician_id, $contract, $contract_id, $selected_date, $action_id, $shift, $log_details, $physician, $duration, $user_id);

                        if ($results->getdata()->message == "Success") {
                            $physician_logs->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
                        } else {
                            if ($results->getdata()->message == "annual_max_shifts_error") {
                                return $results->getdata()->message;
                            } else {
                                $log_error_for_dates[$selected_date] = $results->getdata()->message;
                            }

                        }

                        // $logdata = PhysicianLog::where('physician_id', '=', $physician_id)
                        //     ->where('contract_id', '=', $contract_id)
                        //     ->where('date', '=', mysql_date($selected_date))
                        //     ->get();
                        // $action_selected = Action::findOrFail($action_id);
                        // //if logs not yet entered , can be able to add (save) any log
                        // if (count($logdata) == 0) {
                        //     $physician_logs = new PhysicianLog();//If called in and call back the not allow for no log
                        //     if($contract->burden_of_call == 0 || ($action_selected->name != "Called-In" && $action_selected->name != "Called-Back")) {
                        //         $result = $physician_logs->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id);
                        //         if ($result != "Success") {
                        //             return $result;
                        //         }
                        //     }
                        // } else {
                        //     $enteredLogEligibility=0;
                        //     foreach ($logdata as $logdata) {
                        //         $ampmflag = $logdata->am_pm_flag;
                        //         // if already entered log is not for full day & currently entering log is also not for full day then log can be saved
                        //         if (($ampmflag != 0) && ($shift != 0)) {
                        //             //if already entered log is for am & currently adding log is for pm or viceversa then logs can be saved
                        //             if ($shift != $ampmflag) {
                        //                 /*$physician_logs = new PhysicianLog();
                        //                 $result= $physician_logs->saveLogs($action_id,$shift,$log_details,$physician,$contract_id,$selected_date,$duration,$user_id);
                        //                 if($result != "Success" ){
                        //                     return $result;
                        //                 }*/
                        //                 $enteredLogEligibility=1;
                        //             }
                        //         }
                        //         //If on call then allow to enter called in and call back
                        //         $action_present = Action::findOrFail($logdata->action_id);
                        //         if($contract->burden_of_call == 0 || $action_present->name == "On-Call"){
                        //             if($contract->burden_of_call == 0 || $action_selected->name != "On-Call") {
                        //                 /*$physician_logs = new PhysicianLog();
                        //                 $result = $physician_logs->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id);
                        //                 if ($result != "Success") {
                        //                     return $result;
                        //                 }*/
                        //                 $enteredLogEligibility=1;
                        //             }
                        //         }
                        //     }
                        //     if($enteredLogEligibility){
                        //         $physician_logs = new PhysicianLog();
                        //         $result = $physician_logs->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id);
                        //         if ($result != "Success") {
                        //             return $result;
                        //         }
                        //     }
                        // }
                    } else {
                        return "Error 90_days";
                    }
                }

                //}
            }
            if (count($log_error_for_dates) > 0) {
                $final_error_message = "You are not allowed to enter log for dates :";
                $cnt = 1;
                foreach ($log_error_for_dates as $error_date => $error_message) {
                    $datetime = strtotime($error_date);
                    $format_date = date('d-M', $datetime);
                    $final_error_message .= "$format_date" . ",";

                    if ($cnt > 2) {
                        $final_error_message .= "\n";
                        $cnt = 1;
                    }
                    $cnt = $cnt + 1;
                }
                $final_error_message = rtrim($final_error_message, ',');
                $final_error_message .= ".";

                return $final_error_message;

            } else {
                return "Success";
            }
        }
    }

    public function approveOnCallLogs($id, $agreement_id)
    {
        $log_ids = Request::all();
        $userData = Auth::user();
        $user_id = $userData->id;
        $physician_log = new PhysicianLog();
        if (array_key_exists('approve_logs', $log_ids)) {
            if (array_key_exists('approve_log_ids', $log_ids)) {
                //foreach ($log_ids['approve_log_ids'] as $log_id) {
                if (count($log_ids['approve_log_ids']) > 0) {
                    $log_id = $log_ids['approve_log_ids'][0];
                    $fetch_physician_id = PhysicianLog::select('*')
                        ->where("id", "=", $log_id)
                        ->first();

                    if ($fetch_physician_id->physician_id != '') {
                        $fetch_signature_ids = DB::table('signature')->select('signature_id')
                            ->where("physician_id", "=", $fetch_physician_id->physician_id)
                            ->orderBy('date', 'desc')
                            ->first();


                        if (count($fetch_signature_ids) > 0) {

                            foreach ($fetch_signature_ids as $fetch_signature_id) {
//                                $result = DB::table('physician_logs')
//                                    ->where('id', $log_id)
//                                    ->update(array(
//                                        'signature' => $fetch_signature_id->signature_id,
//                                        'approval_date' => date("Y-m-d"),
//                                        'approved_by' => $user_id,
//                                        'approving_user_type' => PhysicianLog::ENTERED_BY_USER
//                                    ));

                                $result = $physician_log->approveLogsDashboard($fetch_physician_id->physician_id, $fetch_physician_id->contract_id, $fetch_signature_id, "practice", $log_ids['dateSelector']);
                            }
                        } else {
//                            $result = DB::table('physician_logs')
//                                ->where('id', $log_id)
//                                ->update(array('approval_date' => date("Y-m-d"),
//                                    'approved_by' => $user_id,
//                                    'approving_user_type' => PhysicianLog::ENTERED_BY_USER
//                                ));
                            return Redirect::back()
                                ->with(['success' => Lang::get('practices.noSignature')
                                ])
                                ->withInput();
                        }
                    }
                }
                //}
                return Redirect::back()
                    ->with(['success' => Lang::get('practices.noPendingLogsForApproval')
                    ])
                    ->withInput();
            } else {
                return Redirect::back()
                    ->with(['error' => Lang::get('practices.noLogs')
                    ])
                    ->withInput();
            }
        }
    }

    public function deleteOnCallEntry($log_id)
    {
        try {
            $log = PhysicianLog::where('id', "=", $log_id)->first();
            $temp_log_detail = $log;

            //issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254

            $contract = Contract::findOrFail($log->contract_id);
            $agreement = Agreement::findOrFail($contract->agreement_id);
            $physicianLog = new PhysicianLog();

            $logdata = PhysicianLog::where('physician_id', '=', $log->physician_id)
                ->where('contract_id', '=', $contract->id)
                ->where('date', '=', mysql_date($log->date))
                ->whereNull('deleted_at')
                ->get();

            if ($contract->burden_of_call == 1 && $contract->on_call_process == 1) {
                $action_name = Action::findOrFail($log->action_id);
                if (count($logdata) > 1 && $action_name->name == "On-Call") {
                    return "Please delete Called Back & Called In logs, before deleting On Call log.";
                }

            }
            //end issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254

            if ($log->next_approver_level == 0 && $log->next_approver_user == 0 && $log->approval_date == "0000-00-00") {
                //$log->delete();
                $delete_log = PhysicianLog::where('id', "=", $log_id)->delete();

                if ($delete_log) {
                    UpdatePaymentStatusDashboard::dispatch($temp_log_detail->physician_id, $temp_log_detail->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $log->date);
                }
                return "SUCCESS";
            } else {
                //    return "ERROR_APPROVED";
                return "Can not delete already approved log";
            }
        } catch (Exception $e) {
            return "ERROR";
        }
    }

    public function getAddManager($id)
    {
        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id)) {
            App::abort(403);
        }

        return View::make('practices/add_manager')->with(compact('practice'));
    }

    public function postAddManager($id)
    {
        $practice = Practice::findOrFail($id);
        $email = Request::input('email');

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $validation = new PracticeValidation();
        $emailvalidation = new EmailValidation();
        if (!$validation->validateAddManager(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }
        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
        }


        if ($practice->users()->where('email', '=', $email)->count() > 0) {
            return Redirect::back()
                ->with(['error' => Lang::get('practices.add_manager_error')])
                ->withInput();
        }

        $user = User::where('email', '=', $email)->first();
        $user->practices()->attach($practice);

        $data = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'practice' => $practice->name,
            'type' => EmailSetup::ADD_MANAGER,
            'with' => [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'practice' => $practice->name
            ]
        ];

        EmailQueueService::sendEmail($data);

        return Redirect::route('practices.managers', $practice->id)->with([
            'success' => Lang::get('practices.add_manager_success')
        ]);
    }

    public function getAddPhysician($id)
    {
        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id)) {
            App::abort(403);
        }

        return View::make('practices/add_physician')->with(compact('practice'));
    }

//physician allowed to add in  multiple hospitals practice

    public function postAddPhysician($practice_id)
    {

        //issue fixed for valdiation for one to many
        $validation = new PhysicianValidation();
        $emailvalidation = new EmailValidation();
        if (!$validation->validateAddExistingPhysician(Request::Input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }
        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
        }


        // $validation = new PhysicianValidation();
        // if (!$validation->validateCreate(Request::Input())) {
        //     if($validation->messages()->has('email') && Request::Input('email') != ''){
        //         $deletedUser = Physician::where('email','=',trim(Request::Input('email')))->onlyTrashed()->first();
        //         if($deletedUser){
        //             $validation->messages()->add('emailDeleted', 'Physician with this email already exist, you can request administrator to restore it.');
        //         }else{
        //             $deletedUser = User::where('email','=',trim(Request::Input('email')))->onlyTrashed()->first();
        //             if($deletedUser){
        //                 $validation->messages()->add('emailDeleted', 'Physician with this email already exist, you can request administrator to restore it.');
        //             }
        //         }
        //     }
        //     return Redirect::back()->withErrors($validation->messages())->withInput();
        // }


        if (!is_practice_owner($practice_id)) {
            App::abort(403);
        }
        $physician_email = Request::input('email');
        $practice = Practice::findOrFail($practice_id);

        //02/03/2021 added physician validation if exist.
        $physician_detail = DB::table("physicians")
            ->where("email", "=", $physician_email)
            ->first();

        if (!$physician_detail) {
            return Redirect::back()
                ->with(['error' => Lang::get('physicians.physician_doesnot_exist')])
                ->withInput();
        } else if ($physician_detail->deleted_at != null) {
            return Redirect::back()
                ->with(['error' => Lang::get('physicians.physician_doesnot_exist')])
                ->withInput();
        }

        //26/12/2020 added validation for currently a physician for this practice
        if ($practice->users()->where('email', '=', $physician_email)->count() > 0) {
            return Redirect::back()
                ->with(['error' => Lang::get('practices.add_physician_error')])
                ->withInput();
        }

        $datetime = Request::input('practice_start_date');
        //12/01/2021 added validation
        if (strtotime($datetime) <= strtotime(date("Y-m-d"))) {

            $result = Physician::addPhysicianToPractice($practice_id, $physician_email);
            if ($result["status"]) {
                return Redirect::route('practices.physicians', $practice_id)->with([
                    'success' => Lang::get('practices.add_physician_success')
                ]);
            } else {
                return Redirect::route('practices.physicians', $practice_id)->with([
                    'error' => Lang::get('practices.add_physician_failure')
                ]);

            }


        } else {
            return Redirect::back()->with([
                'error' => Lang::get('Practice start date should be less than or equal to current date.')
            ]);
        }


        // return View::make('practices/add_physician')->with(compact('practice'));
    }

    public function getCreateManager($id)
    {
        $practice = Practice::findOrFail($id);

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        return View::make('practices/create_manager')->with(compact('practice'));
    }

    public function postCreateManager($id)
    {
        $practice = Practice::findOrFail($id);

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $hospital_password_expiration_months = DB::table("practices")
            ->select("hospitals.password_expiration_months as password_expiration_months")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->where("practices.id", "=", $practice->id)
            ->pluck('password_expiration_months');
        //$expiration_date = new DateTime("+".max($hospital_password_expiration_months)." months");

        $result = User::postCreate();
        if ($result["status"]) {
            $user = $result["data"];
            $user->practices()->attach($practice);

            $data = [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $user->password_text,
                'practice' => $practice->name
            ];

            //Remove as per request 31 Dec 2018 by 1101
            /*Mail::send('emails/practices/create_manager', $data, function ($message) use ($data) {
                $message->to($data['email'], $data['name']);
                $message->subject('Practice Manager Account');
            });*/

            return Redirect::route('practices.managers', $practice->id)->with([
                'success' => Lang::get('practices.create_manager_success')
            ]);
        } else {
            if (isset($result["validation"])) {
                return Redirect::back()->withErrors($result["validation"]->messages())->withInput();
            } else {
                return Redirect::back()
                    ->with(['error' => Lang::get('practices.create_manager_error')])
                    ->withInput();
            }
        }
    }

    public function getDeleteManager($practice_id, $manager_id)
    {
        $practice = Practice::findOrFail($practice_id);
        $manager = User::findOrFail($manager_id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $deleteManager = Practice::deleteManager($practice_id, $manager_id);
        if (!$deleteManager) {
            return Redirect::back()->with([
                'error' => Lang::get('practices.delete_manager_error')
            ]);
        }

        /*if (!$manager->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('practices.delete_manager_error')
            ]);
        }*/

        return Redirect::back()->with([
            'success' => Lang::get('practices.delete_manager_success')
        ]);
    }

    public function getPhysicians($id)
    {
        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 3),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['npi', 'email', 'last_name', 'first_name', 'seen_at', 'created_at'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        /*  $data = $this->query('physician', $options, function ($query, $options) use ($practice) {
              return $query->where('physicians.practice_id', '=', $practice->id);
          });*/

        $data = $this->query('Physician', $options, function ($query, $options) use ($practice) {
            return $query->select('physicians.id', 'physicians.npi', 'physicians.email', 'physicians.last_name', 'physicians.first_name', 'physicians.created_at')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->where('physician_practices.practice_id', '=', $practice->id)
                ->whereRaw("physician_practices.start_date <= now()")
                ->whereRaw("physician_practices.end_date >= now()")
                //    ->where('physician_practices.hospital_id', '=', $practice->hospital_id)
                //get physician list from physician_practice for multiple hospital by 1254
                ->whereNull('physician_practices.deleted_at')->distinct();
        });

        $data['practice'] = $practice;
        $data['table'] = View::make('practices/_physicians')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/physicians')->with($data);
    }

    public function getCreatePhysician($id)
    {
        $practice = Practice::findOrFail($id);
        $specialties = options(Specialty::orderBy('name', 'ASC')->get(), 'id', 'name');
        // $physician_types = options(PhysicianType::all(), 'id', 'type');

        // checking invoice type for validation
        // $result_hospital = Hospital::findOrFail($practice->hospital_id);
        // $invoice_type = $result_hospital->invoice_type;

        if (!is_practice_owner($practice->id))
            App::abort(403);

        return View::make('practices/create_physician')->with(compact('practice', 'specialties'));
    }

    public function postCreatePhysician($id)
    {
        if (!is_practice_owner($id))
            App::abort(403);

        $result = Physician::createPhysician($id);
        return $result;
    }

    public function getReports($id)
    {


        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];


        $data = $this->query('PracticeReport', $options, function ($query, $options) use ($practice) {
            return $query->where('practice_id', '=', $practice->id);
        });


        $contract_types = ContractType::getPracticeOptions($practice->id);
        $default_contract_key = key($contract_types);

        $data['practice'] = $practice;
        $data['table'] = View::make('practices/_reports')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_types;
        $data['agreements'] = Agreement::getPracticeAgreementData($practice->id, $data["contract_type"]);
        $data['form'] = View::make('layouts/_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/reports')->with($data);
    }

    public function postReports($id)
    {

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

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
        //log::info("practice localtimeZone",array($localtimeZone));

        $practice = Practice::findOrFail($id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $months = [];


        if ($agreement_ids == null || count($agreement_ids) == 0) {
            return Redirect::back()->with([
                'error' => Lang::get('practices.report_selection_error')
            ]);
        }

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
        }

        $agreement_ids = implode(',', $agreement_ids);
        $months = implode(',', $months);

        Artisan::call('reports:practice', [
            'practice' => $practice->id,
            'contract_type' => Request::input('contract_type'),
            'agreements' => $agreement_ids,
            'months' => $months,
            'localtimeZone' => $localtimeZone
        ]);

        if (!PracticeReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PracticeReportCommand::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PracticeReportCommand::$message),
            'report_id' => PracticeReportCommand::$report_id
        ]);
    }

    public function getReport($practice_id, $report_id)
    {
        $practice = Practice::findOrFail($practice_id);
        $report = $practice->reports()->findOrFail($report_id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $filename = practice_report_path($practice, $report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteReport($practice_id, $report_id)
    {
        $practice = Practice::findOrFail($practice_id);
        $report = PracticeReport::findOrFail($report_id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('practices.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('practices.delete_report_success')
        ]);
    }

    public function getContracts()
    {
        $physicianId = Request::input("physicianId");
        $contractId = Request::input("contractId");
        //physician to multiple hosptial by 1254
        $hospitalId = Request::input("hospitalId");

        //issue fixed for one to many
        if ($physicianId && $contractId) {
            $physician = Physician::findOrFail($physicianId);
            //physician to multiple hospital by  1254 : get practice id of selected contract
//            $practice_id = DB::table("contracts")
//                                     ->select("contracts.practice_id")
//                                     ->where('contracts.id','=',$contractId)
//                                     ->first();

            $practice_id = DB::table("physician_contracts")
                ->select("physician_contracts.practice_id")
                ->where('physician_contracts.contract_id', '=', $contractId)
                ->where('physician_contracts.physician_id', '=', $physicianId)
                ->whereNull('physician_contracts.deleted_at')
                ->first();

            if (!$practice_id) {
                return [];
            }
            //drop column practice_id from table 'physicians' changes by 1254
            //$physician->practice_id = $practice_id->practice_id;

            $hospitals_override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $hospitalId)
                ->where('is_active', '=', 1)
                ->get();

            $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $hospitalId)
                ->where('is_active', '=', 1)
                ->get();

            $active_contracts = $physician->contracts()
                ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.payment_frequency_type")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                ->whereRaw("practices.id = $practice_id->practice_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                ->get();

            $selected_contract = Contract::findOrFail($contractId);

            ////call-coverage-duration  by 1254 : added call to getRecentlogs in case for other physicians where contract id is not match
            // or where for other physician getting  null active contracts once we get all recent log, will get total duration for other physician


            $contracts = [];
            foreach ($active_contracts as $contract) {
                $today = date('Y-m-d');
                /*
                  @added on :2018/12/27
                  @description: Check if agreement end date is same or different with manual_contract_end_date(newly added condition)
                  - if found same get the valid upto date
                  - if found different get manual_contract_end_date
                  - for old data check if 'manual_contract_end_date' is '0000-00-00' as 'manual_contract_end_date' is added on 2018/12/26
                 */
                $valid_upto = $contract->manual_contract_valid_upto;
                if ($valid_upto == '0000-00-00') {
                    $valid_upto = $contract->manual_contract_end_date;
                }
                /*}else{
                    $valid_upto = $contract->manual_contract_end_date;
                }*//* remove to add valid upto to contract*/
                if ($valid_upto > $today) {
                    if ($contract->deadline_option == '1') {
                        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                    } else {
                        //if($contract->contract_type_id == ContractType::ON_CALL){
                        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                            $contract_logEntry_Deadline_number = 90;
                        } else {
                            $contract_logEntry_Deadline_number = 365;
                        }
                    }
                    $contract->contract_logentry_deadline_number = $contract_logEntry_Deadline_number;

                    $physicians_contract_data = [];
                    $physicians_other_contract_data = [];
                    $total_duration_log_details = [];
                    //if($contract->contract_type_id == ContractType::ON_CALL){
                    if (($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
                        && (($selected_contract->payment_type_id == PaymentType::PER_DIEM || $selected_contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS))) {
                        //$physicians_contract_data = $this->getPhysiciansContractData($contract);
                        $physicians_other_contract_data = Contract::getOtherContractData($contract, $physician);
                        //call-coverage by 1254 : added to pass total_duration of all log for agreements
                        $total_duration_log_details = Contract::getTotalDurationLogs($contract);
                    }
                    if ($contract->id == $contractId) {
                        $approve_logs = $this->getApproveLogs($contract, $physician->id);
                        $date_selectors = [];
                        $renumbered = [];
                        array_push($date_selectors, 'All');
                        if (count($approve_logs) > 0) {
                            foreach ($approve_logs as $log) {
                                array_push($date_selectors, $log['date_selector']);
                            }
                            $date_selectors = array_unique($date_selectors);
                            $renumbered = array_merge($date_selectors, array());
                            json_encode($renumbered);
                        }
                        $data['schedules'] = OnCallSchedules::getSchedule($contract);
                        $data['approved_logs_months'] = $this->getApprovedLogsMonths($contract, $physician);
                        $data['current_month_logs_days_duration'] = self::currentMonthLogDayDuration($contract);
                        //call-coverage-duration  by 1254 : physician id passed to data for showing recent logs only for current physician or owner
                        $data['phyisican_id'] = $physician->id;
                        $data['hospitals_override_mandate_details'] = $hospitals_override_mandate_details;

                        $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;
                        $data['payment_type_id'] = $contract->payment_type_id;
                        $data['mandate_details'] = $contract->mandate_details == 1 ? true : false;

                        // Sprint 6.1.6 Start
                        // $data['hourly_summary'] = array();
                        // if($contract->payment_type_id == PaymentType::HOURLY){
                        //     $data['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data['recent_logs'], $contract, $physician->id);
                        // }
                        // Sprint 6.1.6 End

                        $get_actions = $this->getContractActions($contract);

                        $categories = array();
                        if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                            foreach ($get_actions as $get_action) {
                                if ($get_action['category_id'] != 0) {
                                    $category = DB::table("action_categories")->select('id', 'name')
                                        ->where('id', '=', $get_action['category_id'])
                                        ->first();

                                    $custom_category = CustomCategoryActions::select('*')
                                        ->where('contract_id', '=', $contract->id)
                                        ->where('category_id', '=', $get_action['category_id'])
                                        ->where('category_name', '!=', null)
                                        ->where('is_active', '=', true)
                                        ->first();

                                    $categories[] = [
                                        'category_id' => $get_action['category_id'],
                                        'category_name' => $custom_category ? $custom_category->category_name : $category->name
                                    ];
                                }
                            }
                            $categories = array_unique($categories, SORT_REGULAR);
                        }

                        $contracts = [
                            "id" => $contract->id,
                            "contract_type_id" => $contract->contract_type_id,
                            "payment_type_id" => $contract->payment_type_id,
                            "enter_by_day" => $contract->enter_by_day == 1 ? true : false,
                            "burden_of_call" => $contract->burden_of_call == 1 ? true : false,
                            "partial_hours" => $contract->partial_hours,
                            "name" => contract_name($contract),
                            "start_date" => format_date($contract->agreement->start_date),
                            "end_date" => format_date($contract->manual_contract_end_date),
//                    "rate"       => formatCurrency($contract->rate),
                            "rate" => $contract->rate,
                            "statistics" => $this->getContractStatistics($contract),
                            "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                            "actions" => $get_actions,  // $this->getContractActions($contract),
                            "schedules" => $data['schedules'],
                            // "recent_logs" => $data['recent_logs'],
                            "on_call_schedule" => $this->getOnCallSchedule($contract, $physician->id),
                            // "approve_logs_view" => $this->getApproveLogsView($contract),
                            "approved_logs_months" => $data['approved_logs_months'],
                            //"physiciansContractData" => $physicians_contract_data,
                            "physiciansContractData" => $physicians_other_contract_data,
                            "total_duration_log_details" => $total_duration_log_details,
                            /*"physiciansContractData" => $this->getPhysiciansContractData($contract),*/
                            "holidays" => $physician->getHolidays(),
                            "custom_action_enabled" => $contract->custom_action_enabled,
                            "log_entry_deadline" => $contract_logEntry_Deadline_number,
                            // "date_selectors" => $renumbered,
                            "partial_hours_calculation" => $contract->partial_hours_calculation,
                            "current_month_logs_days_duration" => $data['current_month_logs_days_duration'],
                            "payment_frequency_type" => $contract->payment_frequency_type,
                            // "hourly_summary" => $data['hourly_summary'],                            // Sprint 6.1.6
                            "categories" => $categories,
                            "quarterly_max_hours" => $contract->quarterly_max_hours,
                            "mandate_details" => $contract->mandate_details == 1 ? true : false,
                            "contract_period_data" => $this->getContractPeriod()
                        ];
                    }
                }
            }

            return $contracts;
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    protected function getApproveLogs($contract, $physician_id = 0)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        // $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"
        $physicianId = Request::input("physician", 0);
        if ($physician_id > 0) {
            $data['physician_id'] = $physician_id;
        } else {
            $p_id = Physician::select('id')->where('email', '=', Auth::user()->email)->first();
            $data['physician_id'] = $p_id->id;
        }
        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        /**
         * #payment_frequency
         * Below factory function is used for fetching the date range and prior_date for respective payment frequency.
         */
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where('physician_logs.physician_id', '=', $data['physician_id'])
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) < 1) {
                //if($contract->contract_type_id == ContractType::ON_CALL)
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if ($log->duration == 1.00) {
                        $duration_data = "Full Day";
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                } else {
                    $duration_data = formatNumber($log->duration);
                }

                $entered_by = "Not available.";
                if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('physicians')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->physician_id)->first();
                        $entered_by = $user->full_name;
                    }
                } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->entered_by)->first();
                        $entered_by = $user->full_name;
                    }
                }

                $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

                foreach ($date_range_res as $date_range_obj) {
                    if (strtotime($log->date) >= strtotime($date_range_obj['start_date']) && strtotime($log->date) <= strtotime($date_range_obj['end_date'])) {
                        $date_selector = format_date($date_range_obj['start_date'], "m/d/Y") . " - " . format_date($date_range_obj['end_date'], "m/d/Y");
                    }
                }

                $results[] = [
                    "id" => $log->id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    // "date_selector" => format_date($log->date,"M-Y"),
                    "date_selector" => $date_selector,
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.'
                ];
            }
        }

        if (count($results) > 0) {
            return $results;
        }

        return [];
    }


    /*function written for fetching contract period */

    public function getApprovedLogsMonths($contract, $physician)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $approval_process = $contract->agreement->approval_process;
        $agreement_month = $agreement_data->months[$agreement_data->current_month];
        // log::info('$agreement_data->months', array($agreement_data));

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        if (count($agreement_data->months) > 1) {
            $prior_month_end_date = date('Y-m-d', strtotime($agreement_data->months[count($agreement_data->months) - 1]->end_date));
        } else {
            $prior_month_end_date = date('Y-m-d', strtotime($agreement_data->months[count($agreement_data->months)]->end_date));
        }
        $approved_range = new StdClass;
        $approved_range->months = [];

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.physician_id", '=', $physician->id)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            //add condition for approval process off
            if ($approval_process == 1) {
                $approval = DB::table('log_approval_history')->select('*')
                    ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
                if (count($approval) > 0) {
                    $isApproved = true;
                }
            } else {
                $isApproved = ($log->approval_date != '0000-00-00' || $log->signature > 0) ? true : false;
            }

            // if($isApproved==true)
            // //if($log->approval_date !='0000-00-00' || $log->signature > 0)
            // {
            //     $d = date_parse_from_format("Y-m-d", $log->date);

            //     if($current_month!=$d["month"]){
            //         $current_month=$d["month"];
            //         $results[] = $d["month"].'-'.$d["year"];
            //         //$results[] = $d["month"];
            //     }
            // }

            if ($isApproved == true) {
                foreach ($agreement_data->months as $index => $date_obj) {
                    $start_date = date("Y-m-d", strtotime($date_obj->start_date));
                    $end_date = date("Y-m-d", strtotime($date_obj->end_date));
                    // log::info('$log->date', array($log->date));
                    // log::info('$start_date', array($start_date));
                    if (strtotime($log->date) >= strtotime($start_date) && strtotime($log->date) <= strtotime($end_date)) {
                        $range_data = new StdClass;
                        $range_data->number = $index;
                        $range_data->start_date = $start_date;
                        $range_data->end_date = $end_date;

                        $approved_range->months[$range_data->number] = $range_data;
                    }


                }
            }
        }
        // return $results;
        return $approved_range->months;
    }

    public function currentMonthLogDayDuration($contract)
    {

        $agreement_data = Agreement::getAgreementData($contract->agreement_id);
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $date_range_with_start_end_date = $res_pay_frequency['date_range_with_start_end_date'];
        $arr_length = count($date_range_with_start_end_date) - 1;

        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');

        $today = date('Y-m-d');

        for ($arr_length; $arr_length >= 0; $arr_length--) {
            $date_obj = $date_range_with_start_end_date[$arr_length];

            if (strtotime($today) >= strtotime($date_obj['start_date']) && strtotime($today) <= strtotime($date_obj['end_date'])) {
                $start_date = $date_obj['start_date'];
                $end_date = $date_obj['end_date'];
                break;
            }
        }

        // log::info('Test', array($res_pay_frequency));

        // $start_date = date('Y-m-01');
        // $end_date = date('Y-m-t');

        return $total_days_hours = $contract->logs()
            ->whereBetween("physician_logs.date", [mysql_date($start_date), mysql_date($end_date)])
            ->sum('physician_logs.duration');

        //    Log::Info('totalHours', array($contract->partial_hours));
    }

    private function getContractActions($contract)
    {
        $results = Action::getActions($contract);
        /*$activities = $contract->actions()
            ->whereIn("action_type_id", [1, 2])
            ->orderBy("action_type_id", "asc")
            ->orderBy("name", "asc")
            ->get();

        $results = [];
        foreach ($activities as $activity) {
            $results[] = [
                "id" => $activity->id,
                "name" => $activity->name,
                "action_type_id" => $activity->action_type_id,
                "action_type" => $activity->action_type->name,
                "duration" => DB::table("action_contract")
                    ->where("contract_id", "=", $contract->id)
                    ->where("action_id", "=", $activity->id)
                    ->pluck("hours")
            ];
        }*/

        return $results;
    }

    public function getActions()
    {
        $agreementId = Request::input('agreementId');
        $physicianId = Request::input('physicianId');

        $physician = Physician::find($physicianId);
        //DB::table('physicians')->where('id', $physicianId)->first();;
        //Physician::where('id', '=',$physicianId)->get();

        $active_contracts = $physician->contracts()
            ->select("contracts.*")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("contracts.agreement_id = " . $agreementId)
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->get();

        $actions = [];

        foreach ($active_contracts as $contract) {
            $actions[] = $this->getContractActions($contract);
        }

        $results = [];
        foreach ($actions as $activities) {
            foreach ($activities as $activity) {
                $results[] = [
                    "id" => $activity['id'],
                    "name" => $activity['name'],
                    "action_type_id" => $activity['action_type_id'],
                    "action_type" => $activity['action_type']
                ];
            }
        }

        return $results;
    }

    private function getContractStatistics($contract)
    {
        $monthNum = 3;
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $monthName = $dateObj->format('M');
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
        }

        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        $date_range_res_count = count($date_range_res);

        if ($date_range_res_count == 1) {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
            $date = with(new DateTime($prior_start_date));
            $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
            $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
        }

        $expected_months = $agreement_data->current_month;
        $expected_hours = $contract->expected_hours * $expected_months;

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
            ->where("physician_logs.date", "<=", now())
            ->sum("duration");

        //if ($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
                ->where("physician_logs.date", "<=", now())
                ->sum("duration");
            $expected_hours = $contract->expected_hours;
        }

        if ($contract->quarterly_max_hours == 1) {
            $remaining_hours = $contract->max_hours - $worked_hours;
        } else {
            $remaining_hours = $expected_hours - $worked_hours;
        }

        /*if ($worked_hours < $expected_hours){
            $remaining_hours = $expected_hours - $worked_hours;
        }else{
            $remaining_hours = $worked_hours - $expected_hours;
        }*/

        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
            ->where("physician_logs.date", "<=", now())
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">", mysql_date($prior_month_end_date))
            ->where("physician_logs.date", "<=", now())
            ->count();

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => $saved_logs,
            "annual_cap" => formatNumber($contract->annual_cap)
        ];
    }

    private function getPriorContractStatistics($contract)
    {

        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        }

        $date_range_res_count = count($date_range_res);

        if ($date_range_res_count > 4) {
            $prior_period_start_date = $date_range_res[$date_range_res_count - 4]['start_date']; //for fetching last prior 3 periods data
        } else {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            if ($date_range_res_count == 1) {
                $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
                $date = with(new DateTime($prior_start_date));
                $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
                $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
            }
        }

        $start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
        $current_date = date('Y-m-d h:m:s', strtotime(mysql_date($agreement_month->now_date)));
        //$prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_data->end_date)));
        // $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_months = $agreement_data->current_month;
        $expected_hours = $contract->expected_hours;

        $current_month_start_date_time = new DateTime($agreement_month->start_date);

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", $current_month_start_date_time)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");

        $total_prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->sum("duration");
        $remaining_hours = $expected_hours - $worked_hours;
        /*if ($worked_hours < $expected_hours){
            $remaining_hours = $expected_hours - $worked_hours;
        }else{
            $remaining_hours = $worked_hours - $expected_hours;
        }*/

        //$total_hours = $contract->logs()->sum("duration");
        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->count();

        $prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($prior_period_start_date))
            ->where("physician_logs.date", "<=", mysql_date($prior_month_end_date))
            //->where("physician_logs.created_at", ">=", $current_month_start_date_time->format('Y-m-d 00:00:00'))
            //->where("physician_logs.created_at", "<=", date('Y-m-d H:i:s'))
            // ->where("physician_logs.date", "<=", date('Y-m-d H:i:s'))
            ->sum("duration");

//        if(($contract->payment_type_id == PaymentType::HOURLY)&&($contract->prior_start_date!='0000-00-00'))
        if (($contract->payment_type_id == PaymentType::HOURLY)) {
            /**
             * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
             */
            if ($contract->prior_start_date != '0000-00-00') {
                $contract_start_date = $contract->prior_start_date;
            } else {
                $contract_start_date = $contract->agreement->start_date;
            }
            $contractFirstYearStartDate = date('Y-m-d', strtotime($contract_start_date));

            if ($contractFirstYearStartDate >= date('Y-m-d', strtotime(mysql_date($prior_period_start_date))) && ($contractFirstYearStartDate <= date('Y-m-d', strtotime(mysql_date($prior_month_end_date))))) {
                $prior_worked_hours = $prior_worked_hours + $contract->prior_worked_hours;
            }
        }

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => $saved_logs,
            "prior_worked_hours" => formatNumber($prior_worked_hours),
            "total_prior_worked_hours" => formatNumber($total_prior_worked_hours)
        ];
    }

    public function getOnCallSchedule($contract, $physician_id)
    {
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            // return physician schedule for current month
            $schedules = DB::table("on_call_schedule")->select("*")
                ->where("physician_id", "=", $physician_id)
                ->whereRaw('MONTH(date) = ?', array(date('m')))
                ->where("contract_id", "=", $contract->id)
                //->whereRaw('date >= CURDATE()')
                ->get();

            $return_schedules = [];
            foreach ($schedules as $schedule) {
                $return_schedules[] = [
                    "shift" => ($schedule->physician_type == 1) ? "AM Shift" : "PM Shift",
                    "date" => format_date($schedule->date, "m/d/Y")
                ];
            }
            return $return_schedules;
        }
        return [];
    }

    /**
     * @param $contract
     * @return array
     */
    //Physician to multiple hospital by 1254
    public function getContractPeriod()
    {
        $physicianId = Request::input("physicianId");
        $contractId = Request::input("contractId");
        //issue fixed for one to many
        $min_days = 0;
        $max_days = 0;

        if (($physicianId) && ($contractId)) {
            $contract = Contract::findOrFail($contractId);
            $start_date = strtotime(format_date($contract->agreement->start_date));
            //$end_date= strtotime(format_date($contract->agreement->end_date));
            $end_date = strtotime(format_date($contract->manual_contract_end_date));
            if ($contract->manual_contract_valid_upto == "0000-00-00") {
                $valid_upto = $end_date;
            } else {
                //$valid_upto = strtotime(format_date($contract->agreement->valid_upto));
                $valid_upto = strtotime(format_date($contract->manual_contract_valid_upto));
            }

            $current_date = strtotime(date("m/d/Y"));
            if ($contract->deadline_option == '1') {
                $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                $contract_logEntry_Deadline_number = 1 - $contract_deadline_days_number->contract_deadline_days;
            } else {
                //if($contract->contract_type_id == ContractType::ON_CALL){
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $contract_logEntry_Deadline_number = -89;
                } else {
                    $contract_logEntry_Deadline_number = -364;
                }
            }

            $last_end_days_date = strtotime(date('m/d/Y', strtotime($contract_logEntry_Deadline_number . ' day', $current_date)));

            $min_days = -$contract_logEntry_Deadline_number;
            $max_days = 0;
            /*if start date is less than 90 days from current date then log can be added only from last 90 days otherwise from start date of agreement */
            if ($start_date < $last_end_days_date) {
                $min_days = $contract_logEntry_Deadline_number;
            } else {
                $datediff = $start_date - $current_date;
                $min_days = floor($datediff / (60 * 60 * 24));
                //if($contract->contract_type_id != ContractType::ON_CALL){
                if ($contract->payment_type_id != PaymentType::PER_DIEM && $contract->payment_type_id != PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    //commented on 17 Jan 2019 for Log Entry Deadline
                    /*$datediff = $start_date - $current_date;
                    $min_days=floor($datediff/(60*60*24));*/
                    if ($min_days < $contract_logEntry_Deadline_number) {
                        $min_days = $contract_logEntry_Deadline_number;
                    }
                }
            }

            /* if end date is greater than current date then logs can be added till today ,
            if end date is less than current date & if valid upto date is greater than current date then logs can be added  till current date
            else logs can not be added */
            if ($end_date <= $current_date) {
                if ($current_date <= $valid_upto) {
                    $datediff = $end_date - $current_date;
                    $max_days = floor($datediff / (60 * 60 * 24));
                } else {
                    $min_days = 0;
                    $max_days = -1;
                }
            } else {
                $max_days = 0;
            }
        }
        return [
            "status" => self::STATUS_FAILURE,
            "min_date" => $min_days,
            "max_date" => $max_days
        ];
    }

    public function getPriorMonthLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physician = $access_token->physician;
            $active_contracts = $physician->contracts()
                ->select("contracts.*")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->get();

            $contracts = [];
            foreach ($active_contracts as $contract) {
                $contracts[] = [
                    "id" => $contract->id,
                    "contract_type_id" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "name" => contract_name($contract),
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->agreement->end_date),
//                    "rate"       => formatCurrency($contract->rate),
                    "rate" => $contract->rate,
                    "statistics" => $this->getContractStatistics($contract),
                    "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                    "actions" => $this->getContractActions($contract),
                    //"recent_logs" => $this->getPriorMonthLogsData($contract),
                    "recent_logs" => $this->getPriorMonthUnapprovedLogs($contract)
                    //"prior_unapproved_logs" => $this -> getPriorMonthUnapprovedLogs($contract),
                ];
            }

            return $contracts;
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getApproveLogsViewRefresh()
    {
        $contractId = Request::input("contract", 1);
        $physicianId = Request::input("physician", 0);
        $contract = Contract::findOrFail($contractId);
        $dateSelector = Request::input("dateSelector", "All");
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        //$prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        // log::info('Test', array($res_pay_frequency));

        if ($physicianId > 0) {
            $data['physician_id'] = $physicianId;
        } else {
            $p_id = Physician::select('id')->where('email', '=', Auth::user()->email)->first();
            $data['physician_id'] = $p_id->id;
        }

        $hospitals_override_mandate_details = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.hospital_id', 'hospitals_override_mandate_details.action_id')
            ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->where("contracts.id", "=", $contractId)
            ->where("hospitals_override_mandate_details.is_active", "=", 1)
            ->get();


        $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospitals_time_stamp_entry.hospital_id', 'hospitals_time_stamp_entry.action_id')
            ->join('practices', 'practices.hospital_id', '=', 'hospitals_time_stamp_entry.hospital_id')
            ->join('contracts', 'contracts.practice_id', '=', 'practices.id')
            ->where("contracts.id", "=", $contractId)
            ->where("hospitals_time_stamp_entry.is_active", "=", 1)
            ->get();


        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where('physician_logs.physician_id', '=', $data['physician_id'])
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        $date_selectors = [];
        array_push($date_selectors, 'All');

        // This line of code is added for the payment frequency filter on physician dashboard for approve logs.
        $temp_range_start_date = '';
        $temp_range_end_date = '';
        if ($dateSelector != "All") {
            $temp_date_selector_arr = explode(" - ", $dateSelector);
            $temp_range_start_date = $temp_date_selector_arr[0];
            $temp_range_end_date = $temp_date_selector_arr[1];
        }

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) < 1) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if ($contract->partial_hours == true) {
                        $duration_data = formatNumber($log->duration);
                    } else {
                        if ($log->duration == 1.00) {
                            $duration_data = "Full Day";
                        } elseif ($log->duration == 0.50) {
                            if ($log->am_pm_flag == 1) {
                                $duration_data = "AM";
                            } else {
                                $duration_data = "PM";
                            }
                        } else {
                            $duration_data = formatNumber($log->duration);
                        }
                    }
                } else {
                    $duration_data = formatNumber($log->duration);
                }

                $entered_by = "Not available.";
                if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('physicians')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->physician_id)->first();
                        $entered_by = $user->full_name;
                    }
                } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->entered_by)->first();
                        $entered_by = $user->full_name;
                    }
                }

                $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

                foreach ($date_range_res as $date_range_obj) {
                    if (strtotime($log->date) >= strtotime($date_range_obj['start_date']) && strtotime($log->date) <= strtotime($date_range_obj['end_date'])) {
                        $date_selector_temp = format_date($date_range_obj['start_date'], "m/d/Y") . " - " . format_date($date_range_obj['end_date'], "m/d/Y");
                    }
                }

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    $custom_action = CustomCategoryActions::select('*')
                        ->where('contract_id', '=', $contract->id)
                        ->where('action_id', '=', $log->action_id)
                        ->where('action_name', '!=', null)
                        ->where('is_active', '=', true)
                        ->first();

                    $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
                }

                array_push($date_selectors, $date_selector_temp);
                if ($dateSelector == "All" || (strtotime($log->date) >= strtotime($temp_range_start_date) && strtotime($log->date) <= strtotime($temp_range_end_date))) {
                    $results[] = [
                        "id" => $log->id,
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                        "date" => format_date($log->date),
                        "date_selector" => format_date($log->date, "M-Y"),
                        "duration" => $duration_data,
                        "created" => $created,
                        "isSigned" => ($log->signature > 0) ? true : false,
                        "note_present" => (strlen($log->details) > 0) ? true : false,
                        "note" => (strlen($log->details) > 0) ? $log->details : '',
                        "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                        "actions" => Action::getActions($contract),
                        "action_id" => $log->action_id,
                        "custom_action" => $log->action->name,
                        "contract_type" => $contract->contract_type_id,
                        "payment_type_id" => $contract->payment_type_id,
                        "shift" => $log->am_pm_flag,
                        "mandate" => $contract->mandate_details,
                        "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                        "log_physician_id" => $log->physician_id,
                        "partial_hours" => $contract->partial_hours,
                        "start_time" => date('g:i A', strtotime($log->start_time)),
                        "end_time" => date('g:i A', strtotime($log->end_time))
                    ];
                }
            }
        }

        // Sprint 6.1.6 Start
        $hourly_summary = array();
        if ($contract->payment_type_id == PaymentType::HOURLY) {
            if ($dateSelector == "All") {
                $hourly_summary = PhysicianLog::getMonthlyHourlySummary($results, $contract, $physicianId);
            } else {
                $worked_hours = 0;
                foreach ($results as $result) {
                    $date = strtotime($result['date']);
                    $worked_hours += $result['duration'];
                }
                $hourly_summary [] = [
                    'payment_type_id' => $contract->payment_type_id,
                    'month_year' => date('m-Y', $date),
                    'worked_hours' => formatNumber($worked_hours, 2),
                    'remaining_hours' => formatNumber((($contract->expected_hours - $worked_hours) > 0) ? $contract->expected_hours - $worked_hours : 0.00, 2),
                    'annual_remaining' => formatNumber((($contract->annual_cap - $worked_hours) > 0) ? $contract->annual_cap - $worked_hours : 0.00, 2),
                    'start_date' => date('Y-m-d', strtotime($agreement_data->start_date)),
                    'end_date' => date('Y-m-t', strtotime($agreement_data->end_date)),
                ];
            }
        }
        // Sprint 6.1.6 End
        if (count($results) > 0) {
            $user_id = Auth::user()->id;
            $data['contract'] = $contract;
            $data['results'] = $results;

            $data['hospitals_override_mandate_details'] = $hospitals_override_mandate_details;

            $period_label = "Period";

            if ($agreement_data->payment_frequency_type == 1) {
                $period_label = "Monthly";
            } else if ($agreement_data->payment_frequency_type == 2) {
                $period_label = "Weekly";
            } else if ($agreement_data->payment_frequency_type == 3) {
                $period_label = "Biweekly";
            } else if ($agreement_data->payment_frequency_type == 4) {
                $period_label = "Quarterly";
            }

            $data['payment_frequency_frequency'] = $period_label;
            $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;
            $data['hourly_summary'] = $hourly_summary;  // Sprint 6.1.6

            // Sprint 6.1.1.8 Start
            $monthly_attestation_questions = [];
            $annually_attestation_questions = [];
            if ($contract->state_attestations_monthly) {
                $monthly_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 1, $data['physician_id']);
            }
            if ($contract->state_attestations_annually) {
                $annually_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 2, $data['physician_id']);
            }

            $data['monthly_attestation_questions'] = count($monthly_attestation_questions) > 0 ? true : false;
            $data['annually_attestation_questions'] = count($annually_attestation_questions) > 0 ? true : false;
            // Sprint 6.1.1.8 End

            $date_selectors = array_unique($date_selectors);
            $renumbered = array_merge($date_selectors, array());
            json_encode($renumbered);
            $data['date_selectors'] = $renumbered;
            $html = View::make('physicians/subview_approve_logs')->with($data)->render();
            $result = [
                "html" => $html,
                "date_selectors" => $renumbered,
                "date_selector" => $dateSelector
            ];
            return $result;
        }

        return "";
    }

    public function getShowRejectedLogs($practiceId, $contractId, $physician_id, $hospitalId = 0)
    {
        Log::info("get show reject ed log", array());
        $practice = Practice::findOrFail($practiceId);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at']
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($practice) {
            return $query->select('users.*')
                ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                ->where('practice_user.practice_id', '=', $practice->id);
        });
        $practice = new Practice();
        $data = $practice->getPracticeContracts($practiceId, $contractId);
        if ($physician_id == 0) {
            $contractId = $contractId;
        } else {
            foreach ($data['physicians'] as $physician) {
                if ($physician['id'] == $physician_id) {
                    $contractId = $physician['contract'];
                } else {
                    //$contractId = $contractId;
                }
            }
        }
        $physician_logs = new PhysicianLog();
        $rejected_logs = $physician_logs->rejectedLogs($contractId, $physician_id);
        $data['rejected_logs'] = $rejected_logs;
        $data['physician_id'] = $physician_id;

        //$data = [];
        return View::make('practices/rejected_logs')->with($data);
    }

    public function getManagerReports($practiceId, $hospitalId)
    {
        $practice = Practice::findOrFail($practiceId);
        $hospital = Hospital::findOrFail($hospitalId);

        $agreements = Request::input("agreements", null);
        $contractType = Request::input("contract_type", -1);
        $show_archived_flag = Request::input("show_archived");

        if ($show_archived_flag == 1) {
            $status1 = true;
        } else {
            $status1 = false;
        }
        $check = false;

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
                ->where("type", "=", 1);
        });

        $contract_types = ContractType::getPracticeOptions($practice->id);
        $default_contract_key = key($contract_types);

        $data['hospital'] = $hospital;
        $data['practice'] = $practice;
        $data['table'] = View::make('practices/_reports_table')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data["contract_type"] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_types;
        $data["physicians"] = Physician::listByPracticeAgreements($agreements, $contractType, $practice->id);
        //$data['agreements'] = Agreement::getHospitalAgreementDataForReports($hospital->id, $data["contract_type"], $show_archived_flag);
        $data['agreements'] = Agreement::getPracticeAgreementData($practice->id, $data["contract_type"]);
        $data['check'] = $check;
        $data['isChecked'] = $status1;
        $data['form'] = View::make('layouts/_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practices/managerReports')->with($data);
    }

    public function postManagerReports($practiceId, $hospitalId)
    {
        $hospital = Hospital::find($hospitalId);

        if (!is_practice_manager())
            App::abort(403);

        $agreement_ids = Request::input("agreements");
        $physician_ids = Request::input("physicians");
        $finalized = Request::input("finalized");
        //print_r($finalized);die;
        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { // Commented by akash
        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }

        $months = [];
        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
        }

        if (Request::input("contract_type") == ContractType::MEDICAL_DIRECTORSHIP) {

            Artisan::call("reports:hospitalMedicalDirectorshipContract", [
                "hospital" => $hospital->id,
                "contract_type" => Request::input("contract_type"),
                "agreements" => implode(",", $agreement_ids),
                "physicians" => implode(",", $physician_ids),
                "months" => implode(",", $months),
                "finalized" => Request::input("finalized")
            ]);

            if (!HospitalReportMedicalDirectorshipContractCommand::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(HospitalReportMedicalDirectorshipContractCommand::$message)
                ]);
            }

            $report_id = HospitalReportMedicalDirectorshipContractCommand::$report_id;
            $report_filename = HospitalReportMedicalDirectorshipContractCommand::$report_filename;

            return Redirect::back()->with([
                'success' => Lang::get(HospitalReportMedicalDirectorshipContractCommand::$message),
                'report_id' => $report_id,
                'report_filename' => $report_filename
            ]);
        } elseif (Request::input("contract_type") == ContractType::ON_CALL) {
            Artisan::call("reports:oncallreportcommand", [
                "hospital" => $hospital->id,
                "contract_type" => Request::input("contract_type"),
                "agreements" => implode(",", $agreement_ids),
                "physicians" => implode(",", $physician_ids),
                "months" => implode(",", $months),
                "finalized" => Request::input("finalized")
            ]);

            if (!OnCallReportCommand::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(OnCallReportCommand::$message)
                ]);
            }

            $report_id = OnCallReportCommand::$report_id;
            $report_filename = OnCallReportCommand::$report_filename;

            return Redirect::back()->with([
                'success' => Lang::get(OnCallReportCommand::$message),
                'report_id' => $report_id,
                'report_filename' => $report_filename
            ]);
        } else {
            return HospitalReport::getReportData($hospital);
            /*Artisan::call("reports:hospital", [
                "hospital"      => $hospital->id,
                "contract_type" => Request::input("contract_type"),
                "agreements"    => implode(",", $agreement_ids),
                "physicians"    => implode(",", $physician_ids),
                "months"        => implode(",", $months),
                "finalized"     => Request::input("finalized")
            ]);

            if (!HospitalReportCommand::$success) {
                return Redirect::back()->with([
                    'error' => Lang::get(HospitalReportCommand::$message)
                ]);
            }

            $report_id = HospitalReportCommand::$report_id;
            $report_filename = HospitalReportCommand::$report_filename;

            return Redirect::back()->with([
                'success' => Lang::get(HospitalReportCommand::$message),
                'report_id' => $report_id,
                'report_filename' => $report_filename
            ]);*/
        }

    }

    /*create for getting months of selected log*/

    public function getManagerReport($practice_id, $report_id)
    {
        $practice = Practice::findOrFail($practice_id);
        $report = PracticeManagerReport::findOrFail($report_id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        $filename = practice_report_path($practice, $report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteManagerReport($practice_id, $report_id)
    {
        $practice = Practice::findOrFail($practice_id);
        $report = PracticeManagerReport::findOrFail($report_id);

        if (!is_practice_owner($practice->id))
            App::abort(403);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('practices.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('practices.delete_report_success')
        ]);
    }

    public function practicemanagerDashboard($user_id)
    {

        if (!is_practice_manager())
            App::abort(403);

        $user = User::findOrFail($user_id);
        $practices = $user->practices()->get();
        $physician_logs = new PhysicianLog();
        $rejected = false;
        $data['user'] = $user;
        $contracts = array();
        //  $hospitals = array();
        //  Log::Info('practices', array($practices));
        foreach ($practices as $practice) {
            //  $physicians = $practice->physicians()->get(); // This line is commented and below line is added to get all the physicians from practices by akash

            $physicians = $practice->physicianspractices()->where('physician_practices.start_date', '<=', mysql_date(now()))->where('physician_practices.end_date', '>=', mysql_date(now()))->get(); // This line is added to get all the physicians from practices by akash (To handle one to many scenario).
            //Add features : hospital name in front of practice name for pract manager login by 1254
            $hospital[] = $practice->hospital()->get();
            foreach ($physicians as $physician) {
                try {
                    $physician_obj = Physician::findOrFail($physician->physician_id);
                    $physician = $physician_obj;

                    $active_contracts = $physician->contracts()
                        ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                        //  ->whereRaw("practices.id = $physician->practice_id")
                        ->whereRaw("practices.id = $practice->id")
                        ->whereRaw("agreements.is_deleted = 0")
                        ->whereRaw("agreements.start_date <= now()")
                        ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                        ->get();
                    foreach ($active_contracts as $contract) {
                        $today = date('Y-m-d');
                        /*
                        @added on :2018/12/27
                        @description: Check if agreement end date is same or different with manual_contract_end_date(newly added condition)
                        - if found same get the valid upto date
                        - if found different get manual_contract_end_date
                        - for old data check if 'manual_contract_end_date' is '0000-00-00' as 'manual_contract_end_date' is added on 2018/12/26
                        */
                        /*if($contract->agreement_end_date == $contract->manual_contract_end_date || $contract->manual_contract_end_date == '0000-00-00'){*//* remove to add valid upto to contract*/
                        //$valid_upto = $contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
                        $valid_upto = $contract->manual_contract_valid_upto;
                        if ($valid_upto == '0000-00-00') {
                            //$valid_upto=$contract->agreement_end_date; /* remove to add valid upto to contract*/
                            $valid_upto = $contract->manual_contract_end_date;
                        }
                        /*}else{
                            $valid_upto = $contract->manual_contract_end_date;
                        }*//* remove to add valid upto to contract*/

                        if ($valid_upto > $today) {
                            if (!$rejected) {
                                $rejected_logs = $physician_logs->rejectedLogs($contract->id, $physician->id);
                                if (count($rejected_logs) > 0) {
                                    foreach ($rejected_logs as $rejected_log) {
                                        if ($rejected_log["enteredBy"] == ($user->last_name . ', ' . $user->first_name)) {
                                            $rejected = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    //do nothing because physician is deleted
                }
            }
        }
        $data['rejected'] = $rejected;
        if (isset($hospital))
            $data['hospitals'] = $hospital;
        return View::make("dashboard/practice_manager")->with($data);
    }

    public function getRejected($user_id, $contact_id, $hospital_id)
    {
        $user = User::findOrFail($user_id);
        if ($hospital_id == 0) {
            $practices = $user->practices()->get();
        } else {
            $practices = $user->practices()->where('practices.hospital_id', '=', $hospital_id)->get();
        }

        // This change is added by akash to solve one to many issue for reject logs on practice manage login(Gets All hospitals list of the practice manager).
        $hospital_ids = Practice::select("hospitals.name as hospital_name", "practices.hospital_id")
            ->join("practice_user", "practice_user.practice_id", "=", "practices.id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->where("practice_user.user_id", "=", $user_id)
            //   ->whereRaw("physician_practices.start_date <= now()")
            //   ->whereRaw("physician_practices.end_date >= now()")
            ->distinct()
            ->pluck("hospital_name", "hospital_id")->toArray();
        $data['hospitals'] = $hospital_ids;

        $data['hospital'] = $hospital_id;
        $data['user'] = $user;
        $data['logs'] = [];
        $data['all_logs'] = [];
        $data['contracts'] = [];
        $data['contract'] = $contact_id;
        $contracts = array();
        $physician_logs = new PhysicianLog();
        foreach ($practices as $practice) {
            $hospital_override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $practice->hospital_id)
                ->where('is_active', '=', 1)
                ->get();

            $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $practice->hospital_id)
                ->where('is_active', '=', 1)
                ->get();

            $data['hospital_override_mandate_details'] = $hospital_override_mandate_details;
            $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;
            //  $physicians = $practice->physicians()->get(); // This line is commented for one to many changes. by akash
            $physicians = $practice->physicianspractices()->where('physician_practices.start_date', '<=', mysql_date(now()))->where('physician_practices.end_date', '>=', mysql_date(now()))->get(); // This line is added for one to many changes. by akash
            foreach ($physicians as $physician) {
                try {
                    $physician_obj = Physician::findOrFail($physician->physician_id);
                    $data['physician'] = $physician_obj;
                    $physician = $physician_obj;
                    $active_contracts = $physician->contracts()
                        ->select("contracts.*", "contract_names.name as contract_name")
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                        ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                        //  ->whereRaw("practices.id = $physician->practice_id")
                        ->whereRaw("practices.id = $practice->id")
                        ->whereRaw("agreements.is_deleted = 0")
                        ->whereRaw("agreements.start_date <= now()")
                        ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                        /*->where(function($query){
                            $query->whereRaw("agreements.end_date >= now()")
                                ->orwhereRaw("agreements.valid_upto >= now()");
                        })*//*remove to add contract end and valid upto date*/
                        /*add to add contract end and valid upto date*/
                        ->where("practices.hospital_id", "=", $practice->hospital_id)
                        ->where(function ($query) {
                            $query->whereRaw("contracts.manual_contract_end_date >= now()")
                                ->orwhereRaw("contracts.manual_contract_valid_upto >= now()");
                        })
                        ->pluck("contract_name", "id");
                    foreach ($active_contracts as $id => $name) {
                        $rejected_logs = $physician_logs->rejectedLogs($id, $physician->id);

                        if (count($rejected_logs) > 0) {
                            foreach ($rejected_logs as $log) {
                                if ($log["enteredBy"] == ($user->last_name . ', ' . $user->first_name)) {
                                    $contracts[$id] = $name;
                                    $log['contract_id'] = $id;
                                    if ($data['hospital'] == 0) {
                                        $data['hospital'] = $practice->hospital_id;
                                    }
                                    if ($data['contract'] == 0) {
                                        $data['contract'] = $id;
                                    }
                                    if ($data['hospital'] == $practice->hospital_id) {
                                        $data['contracts'] = $contracts;
                                    }
                                    $log['physician_name'] = $physician['last_name'] . ', ' . $physician['first_name'];
                                    array_push($data['all_logs'], $log);
                                }
                            }
                            //  break;
                        } else {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    //do nothing because physician is deleted
                }
            }
        }

        foreach ($data['all_logs'] as $log) {
            if ($log['contract_id'] == $data['contract']) {
                array_push($data['logs'], $log);
            }
        }
        return View::make("physicians.rejectedLogs")->with($data);
    }

    public function getPsaTracking($practice_id)
    {
        //call function in contract to get data
        $contract_obj = new Contract();
        $data = $contract_obj->getPsaTrackingData($practice_id, "practice", "All");
        $physicians = [];
        $physicians[0] = 'All';
        foreach ($data as $key => $physician) {
            $physicians['physician_div_' . $physician->id] = $physician->last_name . ', ' . $physician->first_name;
        }
        $data = [
            'data' => $data,
            'physicians' => $physicians
        ];
        return View::make('dashboard/psaTrackingDashboard')->with($data);
    }

    private function getPhysiciansContractData($contract)
    {
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $contractsInAgreement = Contract::where('agreement_id', '=', $agreement->id)
            ->get();

        $contractData = [];
        foreach ($contractsInAgreement as $contractInAgreement) {
            /**
             * getContractsPerContractID currently returns recent logs
             * will update 'method name' or 'return data' as per requirement
             */
            if ($contract->id != $contractInAgreement->id) {
                $contractData[] = $this->getContractsPerContractID($contractInAgreement);
            }
        }
        return $contractData;
    }

    public function getContractsPerContractID($contract)
    {
//        $physicianId = $contract->physician_id;
        $contractId = $contract->id;

        $contract_physician = PhysicianContracts::where('contract_id', '=', $contract->id)->whereNull('deleted_at')->get();

        $contracts = [];

        if (count($contract_physician) > 0) {
            foreach ($contract_physician as $contract_physician_obj) {
                if ($contract_physician_obj->physician_id) {
                    $physicianId = $contract_physician_obj->physician_id;
                    $physician = Physician::find($physicianId);
                    if ($physician) {
                        $active_contracts = $physician->contracts()
                            ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                            ->whereRaw("agreements.start_date <= now()")
                            ->get();


                        foreach ($active_contracts as $contract) {
                            //$valid_upto=$contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
                            $valid_upto = $contract->manual_contract_valid_upto;
                            if ($valid_upto == '0000-00-00') {

                                //$valid_upto=$contract->agreement_end_date;/* remove to add valid upto to contract*/
                                $valid_upto = $contract->manual_contract_end_date;
                            }
                            $today = date('Y-m-d');
                            if ($valid_upto > $today) {
                                if ($contract->id == $contractId) {
                                    $data['recent_logs'] = $this->getRecentLogs($contract);
                                    /*     $hospital_id = Contract::join("practices", "practices.id", "=", "contracts.practice_id")
                                             ->where("contracts.id", "=", $contract->id)->pluck("practices.hospital_id")->toArray();
                                         $hospitalId = $hospital_id[0];

                                         $data['total_duration_log_details'] = $this->getTotalDurationLogs($contract,$physicianId,$hospitalId);*/
                                    $contracts = [
                                        "id" => $contract->id,
                                        "contract_type_id" => $contract->contract_type_id,
                                        "payment_type_id" => $contract->payment_type_id,
                                        "name" => contract_name($contract),
                                        "start_date" => format_date($contract->agreement->start_date),
                                        //"end_date" => format_date($contract->agreement->end_date),
                                        "end_date" => format_date($contract->manual_contract_end_date),
//                    "rate"       => formatCurrency($contract->rate),
                                        "rate" => $contract->rate,
                                        "recent_logs" => $data['recent_logs'],
                                        //"total_duration_log_details" =>$data['total_duration_log_details']
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $contracts;

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }


    private function getRecentLogs($contract, $physicianId = 0, $practiceId = 0)
    {
        //if($contract->contract_type_id == ContractType::ON_CALL && $contract->deadline_option == 1)
        if (($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->deadline_option == '1') {
            $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
            $contract_Deadline = $contract_deadline_days_number->contract_deadline_days;
        } else {
            $contract_Deadline = 90;
        }
        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date > date(now() - interval " . $contract_Deadline . " day)")
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //call-coverage-duration  by 1254
        $log_dates = [];

        foreach ($recent_logs as $log) {
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }

            //if($contract->contract_type_id == ContractType::ON_CALL)
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {

                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call" || $log->action->name == "Called-Back" || $log->action->name == "Called-In") {
                            $duration_data = "On call full Day";
                        } else {
                            $duration_data = "Full Day";
                        }
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }

            } else if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

                /**
                 * This else if is added for per diem uncompensated logs.
                 */
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call/Uncompensated") {
                            $duration_data = "On call Uncompensated Day";
                        } else {
                            $duration_data = "On call Uncompensated Day";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }

            } else {
                $duration_data = formatNumber($log->duration);
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $entered_by = $user->full_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->entered_by)->first();
                    $entered_by = $user->full_name;
                }
            }

            $approved_by = "-";
            if ($log->approving_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->approved_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $approved_by = $user->full_name;
                }
            } elseif ($log->approving_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->approved_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->approved_by)->first();
                    $approved_by = $user->full_name;
                }
            }
            $total_duration = 0.0;
            if ($contract->partial_hours == 1) {

                $physicianLog = new PhysicianLog();
                $total_duration = $physicianLog->getTotalDurationForPartialHoursOn($log->date, $log->action_id, $contract->agreement_id);

            }

            if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */
            $results[] = [
                "id" => $log->id,
                "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                "date" => format_date($log->date),
                "date_selector" => format_date($log->date, "M-Y"),
                "duration" => $duration_data,
                "created" => $created,
                "isSigned" => ($log->signature > 0) ? true : false,
                "note_present" => (strlen($log->details) > 0) ? true : false,
                "note" => (strlen($log->details) > 0) ? $log->details : '',
                "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                "approvedBy" => (strlen($approved_by) > 0) ? $approved_by : '-',
                "approvedDate" => ($log->approval_date == '0000-00-00') ? 'NotApproved' : 'Approved',
                "payment_type_id" => $contract->payment_type_id,
                "actions" => Action::getActions($contract),
                "action_id" => $log->action_id,
                "custom_action" => $log->action->name,
                "contract_type" => $contract->contract_type_id,
                "shift" => $log->am_pm_flag,
                "mandate" => $contract->mandate_details,
                "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                "log_physician_id" => $log->physician_id,
                "isApproved" => $isApproved,
                //call-coverage-duration  by 1254
                "partial_hours" => $contract->partial_hours,
                "contract_id" => $contract->id,
                "total_duration" => $total_duration,
                "partial_hours_calculation" => $contract->partial_hours_calculation,
                "start_time" => date('g:i A', strtotime($log->start_time)),
                "end_time" => date('g:i A', strtotime($log->end_time))
            ];

        }

        return $results;
    }

    private function getPhysicianContracts($physicianId, $contractId)
    {
        $physician = Physician::findOrFail($physicianId);
        $active_contracts = $physician->contracts()
            ->select("contracts.*")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->get();

        $practice_id = DB::table("physician_contracts")
            ->select("physician_contracts.practice_id")
            ->where('physician_contracts.contract_id', '=', $contractId)
            ->where('physician_contracts.physician_id', '=', $physicianId)
            ->whereNull('physician_contracts.deleted_at')
            ->first();

        $contracts = [];
        foreach ($active_contracts as $contract) {
            if ($contract->id == $contractId) {
                $data['recent_logs'] = $this->getRecentLogs($contract, $physician->id, $practice_id->practice_id);

                $hospitals_override_mandate_details = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.hospital_id, hospitals_override_mandate_details.action_id')
                    ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where("contracts.id", "=", $contractId)
                    ->where('hospitals_override_mandate_details.is_active', '=', 1)
                    ->get();

                $data['hospitals_override_mandate_details'] = $hospitals_override_mandate_details;

                $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospitals_time_stamp_entry.hospital_id, hospitals_override_mandate_details.action_id')
                    ->join('practices', 'practices.hospital_id', '=', 'hospitals_time_stamp_entry.hospital_id')
                    ->join('contracts', 'contracts.practice_id', '=', 'practices.id')
                    ->where("contracts.id", "=", $contractId)
                    ->where('hospitals_time_stamp_entry.is_active', '=', 1)
                    ->get();

                $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;

                // Sprint 6.1.6 Start
                $data['hourly_summary'] = array();
                if ($contract->payment_type_id == PaymentType::HOURLY) {
                    $data['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data['recent_logs'], $contract, $contract->physician_id);
                }
                // Sprint 6.1.6 End

                $data['mandate_details'] = $contract->mandate_details == 1 ? true : false;
                $contracts = [
                    "id" => $contract->id,
                    "contract_type_id" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "name" => contract_name($contract),
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->agreement->end_date),
//                    "rate"       => formatCurrency($contract->rate),
                    "rate" => $contract->rate,
                    "statistics" => $this->getContractStatistics($contract),
                    "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                    "actions" => $this->getContractActions($contract),
                    "recent_logs" => $data['recent_logs'],
                    "recent_logs_view" => View::make('practices/subview_recent_logs')->with($data)->render(),
                    "on_call_schedule" => $this->getOnCallSchedule($contract, $physician->id),
                    "approve_logs_view" => $this->getApproveLogsView($contract, $physician->id, $practice_id - practice_id),
                    "hourly_summary" => $data['hourly_summary'],                // Sprint 6.1.6
                ];
            }
        }
        return $contracts;
    }

    // Below function is added to get the current months total days/duration to display to the physician.

    protected function getApproveLogsView($contract, $physician_id, $practice_id)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        /**
         * #payment_frequency
         * Below factory function is used for fetching the date range and prior_date for respective payment frequency.
         */
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];

        $hospitals_override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $agreement_data->hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $agreement_data->hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where('physician_logs.physician_id', '=', $physician_id)
            ->where('physician_logs.practice_id', '=', $practice_id)
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) < 1) {
                //if($contract->contract_type_id == ContractType::ON_CALL)
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if ($contract->partial_hours == true) {
                        $duration_data = formatNumber($log->duration);
                    } else {
                        if ($log->duration == 1.00) {
                            $duration_data = "Full Day";
                        } elseif ($log->duration == 0.50) {
                            if ($log->am_pm_flag == 1) {
                                $duration_data = "AM";
                            } else {
                                $duration_data = "PM";
                            }
                        } else {
                            $duration_data = formatNumber($log->duration);
                        }
                    }
                } else {
                    $duration_data = formatNumber($log->duration);
                }

                $entered_by = "Not available.";
                if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('physicians')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->physician_id)->first();
                        $entered_by = $user->full_name;
                    }
                } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->entered_by)->first();
                        $entered_by = $user->full_name;
                    }
                }

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    $custom_action = CustomCategoryActions::select('*')
                        ->where('contract_id', '=', $contract->id)
                        ->where('action_id', '=', $log->action_id)
                        ->where('action_name', '!=', null)
                        ->where('is_active', '=', true)
                        ->first();

                    $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
                }

                $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

                $results[] = [
                    "id" => $log->id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    "date_selector" => format_date($log->date, "M-Y"),
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                    "actions" => Action::getActions($contract),
                    "action_id" => $log->action_id,
                    "custom_action" => $log->action->name,
                    "contract_type" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "shift" => $log->am_pm_flag,
                    "mandate" => $contract->mandate_details,
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "log_physician_id" => $log->physician_id,
                    "log_physician_id" => $log->physician_id,
                    //call-coverage-duration  by 1254 : added partial hours
                    "partial_hours" => $contract->partial_hours,
                    "start_time" => date('g:i A', strtotime($log->start_time)),
                    "end_time" => date('g:i A', strtotime($log->end_time))
                ];
            }
        }

        // Sprint 6.1.6 Start
        $hourly_summary = array();
        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $hourly_summary = PhysicianLog::getMonthlyHourlySummary($results, $contract, $physician_id);
        }
        // Sprint 6.1.6 End

        if (count($results) > 0) {
            $user_id = Auth::user()->id;
            $data['contract'] = $contract;
            $data['physician_id'] = $physician_id;
            $data['results'] = $results;

            $data['hospitals_override_mandate_details'] = $hospitals_override_mandate_details;

            $period_label = "Period";

            if ($agreement_data->payment_frequency_type == 1) {
                $period_label = "Monthly Period";
            } else if ($agreement_data->payment_frequency_type == 2) {
                $period_label = "Weekly Period";
            } else if ($agreement_data->payment_frequency_type == 3) {
                $period_label = "Biweekly Period";
            } else if ($agreement_data->payment_frequency_type == 4) {
                $period_label = "Quarterly Period";
            }

            $data['payment_frequency_frequency'] = $period_label;

            $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;
            $data['hourly_summary'] = $hourly_summary;  // Sprint 6.1.6
            // Sprint 6.1.1.8 Start
            $monthly_attestation_questions = [];
            $annually_attestation_questions = [];
            if ($contract->state_attestations_monthly) {
                $monthly_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 1, $physician_id);
            }
            if ($contract->state_attestations_annually) {
                $annually_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 2, $physician_id);
            }

            $data['monthly_attestation_questions'] = count($monthly_attestation_questions) > 0 ? true : false;
            $data['annually_attestation_questions'] = count($annually_attestation_questions) > 0 ? true : false;
            // Sprint 6.1.1.8 End

            return View::make('practices/subview_approve_logs')->with($data)->render();
        } elseif (count($results) == 0 && $contract->payment_type_id == 4) {
            return "<div>No logs to approve.</div>";
        }

        return "";
    }
}
