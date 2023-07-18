<?php

namespace App\Http\Controllers;

use App\HospitalFeatureDetails;
use App\Http\Controllers\Validations\AgreementValidation;
use App\Console\Commands\HospitalReportCommand;
use App\Console\Commands\PaymentStatusReport;
use App\Console\Commands\HospitalActiveContractsReportCommand;
use App\Console\Commands\HospitalLawsonInterfacedInvoiceCommand;
use App\Console\Commands\HospitalLawsonInterfacedReportCommand;
use App\Console\Commands\HospitalInvoiceCommand;
use App\Console\Commands\RehabHospitalInvoiceCommand;
use App\Group;
use App\Agreement;
use App\Hospital;
use App\LogApproval;
use App\PhysicianContracts;
use App\PaymentType;
use App\User;
use App\Physician;
use App\PhysicianLog;
use App\Contract;
use App\ContractType;
use App\InvoiceNote;
use App\State;
use App\ApprovalManagerType;
use App\ApprovalManagerInfo;
use App\PracticeType;
use App\Practice;
use App\HospitalReport;
use App\HospitalInterfaceImageNow;
use App\HospitalInterfaceLawson;
use App\InterfaceType;
use App\HospitalAmionDetails;
use App\Amount_paid;
use App\ContractRate;
use App\PhysicianPractices;
use App\InterfaceTankLawson;
use App\HospitalInvoice;
use App\DutyManagement;
use DateTime;
use DateTimeZone;
use App\Http\Controllers\Validations\HospitalInterfaceValidation;
use App\Http\Controllers\Validations\HospitalValidation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\File;
use App\Services\EmailQueueService;
use App\Http\Controllers\Validations\EmailValidation;
use App\customClasses\EmailSetup;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\FacilityType;
use function App\Start\is_hospital_owner;
use function App\Start\is_physician_owner;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\hospital_report_path;

class HospitalsController extends ResourceController
{
    protected $requireAuth = true;

    public function getIndex()
    {
        $options = [
            'filter' => Request::input('filter', 1),
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['npi', 'name', 'state_id', 'expiration', 'created_at'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        if ($this->currentUser->group_id == Group::PRACTICE_MANAGER) {
            App::abort(403);
        }

        $data = $this->query('Hospital', $options, function ($query, $options) {
            switch ($options['filter']) {
                case 1:
                    $query = $query->where('archived', '=', false);
                    break;
                case 2:
                    $query = $query->where('archived', '=', true);
                    break;
            }

            if ($this->currentUser->group_id == Group::HOSPITAL_ADMIN ||
                $this->currentUser->group_id == Group::SUPER_HOSPITAL_USER ||
                $this->currentUser->group_id == Group::HOSPITAL_CFO
            ) {
                $query = $query->select('hospitals.*')
                    ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                    ->where('hospital_user.user_id', '=', $this->currentUser->id);
            }

            return $query;
        });
        $i = 0;
        foreach ($data['items'] as $hospital) {
            $expiration_dt = Agreement::where('agreements.hospital_id', '=', $hospital->id)
                ->where('agreements.archived', '=', 0)
                ->where('agreements.is_deleted', '=', 0)
                ->max('end_date');
            if (!is_null($expiration_dt)) {
                $data['items'][$i]->expiration = $expiration_dt;
            }
            $i++;
        }

        $data['type'] = Request::input('type', 0);
        $data['table'] = View::make('hospitals/partials/table')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/index')->with($data);
    }

    public function getCreate()
    {
        $states = options(State::all(), 'id', 'name');
        $facility_types = options(FacilityType::all(), 'id', 'extension');

        return View::make('hospitals/create')->with(['states' => $states, 'facility_types' => $facility_types]);
    }

    public function getShow($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $physician_count_exclude_onee = DB::table("contracts")
            ->select(DB::raw("distinct(physician_contracts.physician_id)"))
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->where("agreements.hospital_id", "=", $id)
            ->whereNull('physicians.deleted_at');
        $physician_count_exclude_one = $physician_count_exclude_onee->get();

        $hospital_user_exclude_onee = DB::table("hospital_user")
            ->select(DB::raw("distinct(hospital_user.user_id)"))
            ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->join("users", "users.id", "=", "hospital_user.user_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->where("agreements.hospital_id", "=", $id)
            ->whereNull('users.deleted_at');
        $hospital_user_exclude_one = $hospital_user_exclude_onee->get();

        $practice_user_count_exclude_onee = DB::table("practice_user")
            ->select(DB::raw("distinct(practice_user.user_id)"))
            ->join("practices", "practices.id", "=", "practice_user.practice_id")
            ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->join("users", "users.id", "=", "practice_user.user_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->where("agreements.hospital_id", "=", $id)
            ->whereNull('users.deleted_at');
        $practice_user_count_exclude_one = $practice_user_count_exclude_onee->get();

        $hospital->added_users = 0;
        $last_mo_start_date = date('Y-m-d 00:00:00', strtotime('first day of last month'));
        $last_mo_end_date = date('Y-m-d 23:59:59', strtotime('last day of last month'));
        foreach ($hospital_user_exclude_one as $hospital_user) {
            $user = User::findOrFail($hospital_user->user_id);
            if ($user->created_at >= $last_mo_start_date && $user->created_at <= $last_mo_end_date) {
                $hospital->added_users += 1;
            }
        }
        foreach ($practice_user_count_exclude_one as $practice_user) {
            $user = User::findOrFail($practice_user->user_id);
            if ($user->created_at >= $last_mo_start_date && $user->created_at <= $last_mo_end_date) {
                $hospital->added_users += 1;
            }
        }
        foreach ($physician_count_exclude_one as $physician_user) {
            $physician = Physician::findOrFail($physician_user->physician_id);
            if ($physician->created_at >= $last_mo_start_date && $physician->created_at <= $last_mo_end_date) {
                $hospital->added_users += 1;
            }
        }

        $contract_count_exclude_onee = DB::table("contracts")
            ->select(DB::raw("contracts.id"))
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("hospitals.archived=0")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('contracts.deleted_at')
            ->where("agreements.hospital_id", "=", $id)
            ->where("agreements.hospital_id", "<>", 113);
        $contract_count_exclude_one = $contract_count_exclude_onee->get();

        $lawson_interfaced_contracts_count_exclude_onee = DB::table("contracts")
            ->select(DB::raw("contracts.id"))
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("hospitals.archived=0")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('contracts.deleted_at')
            ->where("agreements.hospital_id", "=", $id)
            ->where("contracts.is_lawson_interfaced", "=", true)
            ->where("agreements.hospital_id", "<>", 113);
        $lawson_interfaced_contracts_count_exclude_one = $lawson_interfaced_contracts_count_exclude_onee->get();

        $recentLogs = PhysicianLog::select('physician_logs.*', 'physicians.first_name as first_name', 'physicians.last_name as last_name')
            ->leftJoin('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->leftJoin('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->leftJoin('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->where('hospitals.id', '=', $hospital->id)
            ->whereNull('physician_logs.deleted_at')
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        // Issue fixes : physician count not match on hospital overview
        $physician_practices = PhysicianPractices::select("*")
            ->where("hospital_id", "=", $id)
            ->whereRaw("start_date <= now()")
            ->whereRaw("end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->whereNull("deleted_at")->get();

        $data['hospital'] = $hospital;
        //  $data['physician_count_exclude_one'] = count($physician_practices);
        $data['physician_count_exclude_one'] = count($physician_count_exclude_one);
        $data['practice_user_count_exclude_one'] = count($practice_user_count_exclude_one);
        $data['hospital_user_count_exclude_one'] = count($hospital_user_exclude_one);
        $data['contract_count_exclude_one'] = count($contract_count_exclude_one);
        $data['lawson_interfaced_contracts_count_exclude_one'] = count($lawson_interfaced_contracts_count_exclude_one);
        $data['added_users'] = $hospital->added_users;
        $data['recentLogs'] = $recentLogs;
        $data['note_display_count'] = Contract:: fetch_note_display_contracts_count($id);
        $data['table'] = View::make('hospitals/_recent_activity')->with($data)->render();

        return View::make('hospitals/show')->with($data);
    }

    public function getEdit($id)
    {
        $hospital = Hospital::findOrFail($id);
        $hospital_feature_details = DB::table('hospital_feature_details')->where("hospital_id", "=", $id)->orderBy('updated_at', 'desc')->first();

        if (empty($hospital_feature_details)) {
            $hospital_feature_details = new HospitalFeatureDetails();
            $hospital_feature_details->performance_on_off = 0;
            $hospital_feature_details->compliance_on_off = 0;
        }

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $hospital->expiration = format_date($hospital->expiration);

        $users = $hospital->users()
            ->select(
                DB::raw("users.id as id"),
                DB::raw("concat(users.last_name, ', ', users.first_name) as name")
            )
            ->orderBy("last_name")
            ->orderBy("first_name")
            ->pluck('name', 'id');
        $primary_user_id = $hospital->hasPrimaryUser() ? $hospital->getPrimaryUser()->id : -1;
        $invoice_notes = InvoiceNote::getInvoiceNotes($id, InvoiceNote::HOSPITAL, $id, 0);

        $data = [
            "hospital" => $hospital,
            "states" => State::pluck('name', 'id'),
            "users" => $users,
            "primary_user_id" => $primary_user_id,
            "note_count" => count($invoice_notes),
            "invoice_notes" => $invoice_notes,
            "hospital_feature_details" => $hospital_feature_details,
            "facility_types"   => FacilityType::pluck('extension', 'id')
        ];

        return View::make('hospitals/edit')->with($data);
    }

    public function postEdit($id)
    {
        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);


        $result = Hospital::editHospital($id);
        return $result;
    }

    public function getAgreements($id)
    {
        $hospital = Hospital::findOrFail($id);
        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        if (is_hospital_owner($hospital->id)) {
            $idAgreementArray = array();
        } else {
            $idAgreementCollection = DB::table('agreement_approval_managers_info')->where('agreement_approval_managers_info.user_id', '=', Auth::user()->id)->get();
            foreach ($idAgreementCollection as $item => $value) {
                $idAgreementArray[] = $value->agreement_id;
            }
        }
        $options = [
            'filter' => Request::input('filter'),
            'sort' => Request::input('sort', 1),
            'order' => Request::input('order', 1),
            'sort_min' => 1,
            'sort_max' => 3,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['name', 'start_date', 'end_date'],
            'per_page' => 9999
        ];

        $data = $this->query('Agreement', $options, function ($query, $options) use ($idAgreementArray, $hospital) {
            switch ($options['filter']) {
                case 0:
                    $query->where('archived', '=', false);
                    break;
                case 1:
                    $query->where('archived', '=', true);
                    break;
            }
            if (count($idAgreementArray) > 0) {
                $query->whereIn('agreements.id', $idAgreementArray);
            }

            $query->where('agreements.is_deleted', '=', 0);
            return $query->where('agreements.hospital_id', '=', $hospital->id);
        });
        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/_agreements')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/agreements')->with($data);
    }

    public function getCreateAgreement($id)
    {
        $hospital = Hospital::findOrFail($id);

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $groups = [
            '2' => Group::findOrFail(2)->name,
            '5' => Group::findOrFail(5)->name
        ];
        $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->where('hospital_user.hospital_id', '=', $hospital->id)
            ->where('group_id', '!=', Group::Physicians)
            ->orderBy("name")
            ->pluck('name', 'id');
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[-1] = "Add New User";
        $users_for_invoice_recipients = $users;
        $users_for_invoice_recipients[0] = "NA";

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';
        //Log::info("approval_manager_type", array($approval_manager_type));
        $payment_frequency_option = [
            '1' => 'Monthly',
            '2' => 'Weekly',
            '3' => 'Bi-Weekly',
            '4' => 'Quarterly'
        ];

        $review_day_range_limit = 28;

        return View::make('hospitals/create_agreement')->with(compact('hospital', 'users', 'groups', 'approval_manager_type', 'users_for_invoice_recipients', 'payment_frequency_option', 'review_day_range_limit'));
    }

    public function postCreateAgreement($id)
    {
        //log::info("approverTypeforLevel=======".Request::input('approverTypeforLevel1'));
        $hospital = Hospital::findOrFail($id);
        $hospital_details = Hospital::findOrFail($hospital->id);
        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        // Below code is added to set the review date range base on payment frequency type.
        if (Request::input("payment_frequency_option") == 1) {
            $review_day_range_limit = 28;
        } else if (Request::input("payment_frequency_option") == 2) {
            $review_day_range_limit = 6;
        } else if (Request::input("payment_frequency_option") == 3) {
            $review_day_range_limit = 14;
        } else if (Request::input("payment_frequency_option") == 4) {
            $review_day_range_limit = 85;
        }

        $validation = new AgreementValidation();
        $emailvalidation = new EmailValidation();
        if (Request::has("on_off")) {
            if (Request::input("on_off") == 1) {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateCreate(Request::input())) {
                        $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                    if (!$emailvalidation->validateEmailDomain(Request::input())) {
                        return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                    }
                } else {
                    if (!$validation->validateCreateforInvoiceOnOff(Request::input())) {
                        $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                }
            } else {
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    if (!$validation->validateOff(Request::input())) {
                        $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }
                } else {
                    if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                        $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                        return Redirect::back()
                            ->withErrors($validation->messages())
                            ->withInput();
                    }

                }
            }
        } else {
            if ($hospital_details->invoice_dashboard_on_off == 1) {
                if (!$validation->validateOff(Request::input())) {
                    $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }
            } else {
                if (!$validation->validateOffForInvoiceOnOff(Request::input())) {
                    $validation->messages()->add('review_day_range_limit', $review_day_range_limit);
                    return Redirect::back()
                        ->withErrors($validation->messages())
                        ->withInput();
                }
            }
        }

        $i = 1;
        $approver_mgr_err = [];
        for ($i = 1; $i < 7; $i++) {
            $manager = Request::input('approval_manager_level' . $i);
            if ($manager == null || $manager == "") {
                $approver_mgr_err['approval_manager_level' . $i] = ['Approver manager cannot be empty.'];
            }
        }
        if (count($approver_mgr_err) > 0) {
            return Redirect::back()
                ->withErrors($approver_mgr_err)
                ->withInput();
        }

        if (Request::input('payment_frequency_option') == null || Request::input('payment_frequency_option') == '') {
            $approver_mgr_err['payment_frequency_option'] = ['Please provide payment frequency option.'];
        }

        if (strtotime(Request::input('frequency_start_date')) > strtotime(Request::input('start_date'))) {
            return Redirect::back()->with([
                'error' => "Payment frequency date should be before or same with agreement start date."
            ])->withInput();
        }


        $agreement = new Agreement;
        $response = $agreement->createAgreement($hospital);
        if ($response["response"] === "error") {
            return Redirect::back()->with([
                'error' => $response["msg"],
                'review_day_range_limit' => $review_day_range_limit
            ])->withInput();
        } else {
            return Redirect::route('hospitals.agreements', $hospital->id)
                ->with(['success' => Lang::get('hospitals.create_agreement_success')]);
        }
    }

    public function getAdmins($id)
    {
        $hospital = Hospital::findOrFail($id);
        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'seen_at', 'created_at'],
            'per_page' => 9999
        ];

        $data = $this->query('User', $options, function ($query, $options) use ($hospital) {
            return $query->select('users.*', 'hospital_user.is_invoice_dashboard_display')
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                ->where('hospital_user.hospital_id', '=', $hospital->id);
        });

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/_admins')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/admins')->with($data);
    }

    public function getAddAdmin($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        return View::make('hospitals/add_admin')->with(['hospital' => $hospital]);
    }

    public function postAddAdmin($id)
    {
        $hospital = Hospital::findOrFail($id);
        $email = Request::input('email');

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $validation = new HospitalValidation();
        $emailvalidation = new EmailValidation();
        if (!$validation->validateAddAdmin(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }
        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
        }

        if ($hospital->users()->where('email', '=', $email)->count() > 0) {
            return Redirect::back()
                ->with(['error' => Lang::get('hospitals.add_admin_error')])
                ->withInput();
        }

        $user = User::where('email', '=', $email)->first();
        $user->hospitals()->attach($hospital);

        return Redirect::route('hospitals.admins', $hospital->id)->with([
            'success' => Lang::get('hospitals.add_admin_success')
        ]);
    }

    public function getCreateAdmin($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (is_super_user() || is_super_hospital_user()) {
            /*  if (is_super_user()) {*/
            $groups = [
                '2' => Group::findOrFail(2)->name,
                '5' => Group::findOrFail(5)->name
            ];
            /*} elseif (is_super_hospital_user()) {
                $groups = [
                    '2' => Group::findOrFail(2)->name
                ];
            }*/

            return View::make('hospitals/create_admin')->with([
                'hospital' => $hospital,
                'groups' => $groups
            ]);
        } else {
            App::abort(403);
        }
    }

    public function postCreateAdmin($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (is_super_user() || is_super_hospital_user()) {
            $result = User::postCreate();
            if ($result["status"]) {
                $user = $result["data"];
                $user->hospitals()->attach($hospital);

                DB::table('hospital_user')->where('user_id', '=', $user->id)->where('hospital_id', '=', $hospital->id)->update(['is_invoice_dashboard_display' => 0]); // Change added for 6.1.1.7

                $data = [
                    'name' => "{$user->first_name} {$user->last_name}",
                    'email' => $user->email,
                    'password' => $user->password_text,
                    'hospital' => $hospital->name
                ];

                //Remove as per request 31 Dec 2018 by 1101
                /*Mail::send('emails/hospitals/create_admin', $data, function ($message) use ($data) {
                    $message->to($data['email'], $data['name']);
                    $message->subject('Hospital Admin Account');
                });*/

                if (Request::input('type') != 'ajax') {
                    return Redirect::route('hospitals.admins', $hospital->id)->with([
                        'success' => Lang::get('hospitals.create_admin_success')
                    ]);
                } else {
                    return ['success' => Lang::get('hospitals.create_admin_success'),
                        'name' => "{$user->first_name} {$user->last_name}",
                        'user_id' => $user->id];
                }
            } else {
                if (isset($result["validation"])) {
                    if (Request::input('type') != 'ajax') {
                        return Redirect::back()->withErrors($result["validation"]->messages())->withInput();
                    } else {
                        return $result["validation"]->messages();
                    }
                } else {
                    if (Request::input('type') != 'ajax') {
                        return Redirect::back()
                            ->with(['error' => Lang::get('hospitals.create_admin_error')])
                            ->withInput();
                    } else {
                        return ['error' => Lang::get('hospitals.create_admin_error')];
                    }
                }
            }
        } else {
            App::abort(403);
        }
    }

    public function postCreate()
    {
        $result = Hospital::createHospital();
        return $result;
    }

    public function getPractices($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        //agreement wise without sorting code
        /*$options = [
           'sort' => Request::input('sort', 2),
           'order' => Request::input('order'),
           'sort_min' => 1,
           'sort_max' => 5,
           'appends' => ['sort', 'order'],
           'field_names' => ['npi', 'name', 'practice_type_id', 'created_at', 'agreements.name']
       ];

       $data = $this->query('Practice', $options, function ($query, $options) use ($hospital) {
           return $query->where('hospital_id', '=', $hospital->id);
       });

       $practice_agreements = DB::table('practices')->select('practices.id', 'agreements.name')
           ->join("physicians", "physicians.practice_id", "=", "practices.id")
           ->join("contracts", "contracts.physician_id", "=", "physicians.id")
           ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
           ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
           //->join("agreements", "agreements.hospital_id", "=", "hospitals.id")
           ->whereRaw("agreements.archived = 0")
           ->whereRaw("contracts.archived = 0")
           //->whereRaw("agreements.end_date >= now()")
           ->where('hospitals.id', '=', $hospital->id)
           ->get();

       $data['agreements']=$practice_agreements;*/

        $practiceOptions = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 4,
            'appends' => ['sort', 'order'],
            'field_names' => ['practices.npi', 'practices.name', 'practices.practice_type_id', 'practices.created_at'],
            'per_page' => 9999
        ];

        $practiceData = $this->query('Practice', $practiceOptions, function ($query, $options) use ($hospital) {
            $query->addSelect('practices.npi as npi', 'practices.name as practice_name',
                'practice_types.name as practice_type', 'practices.id as id',
                'practices.created_at as created_at');
            $query->join("practice_types", "practice_types.id", "=", "practices.practice_type_id");
            return $query->where('practices.hospital_id', '=', $hospital->id);
        });


        //agreement wise sorting
        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['practices.npi', 'practices.name', 'practices.practice_type_id', 'practices.created_at', 'agreements.name'],
            'per_page' => 9999
        ];

        /*below where clause  of is_Deleted is added for soft delete
            Code modified_on: 11/04/2016
            */
        $data = $this->query('Practice', $options, function ($query, $options) use ($hospital) {
            $query->addSelect('practices.npi as npi', 'practices.name as practice_name',
                'practice_types.name as practice_type', 'practices.id as id',
                'practices.created_at as created_at');
            $query->join("physician_practice_history", "physician_practice_history.practice_id", "=", "practices.id");

            $query->join("contracts", "contracts.physician_id", "=", "physician_practice_history.physician_id");
            $query->join("practice_types", "practice_types.id", "=", "practices.practice_type_id");
            $query->join("agreements", function ($join) {
                $join->on("contracts.agreement_id", "=", "agreements.id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            });
            $query->join("hospitals", "practices.hospital_id", "=", "hospitals.id");
            $query->whereRaw("agreements.archived = 0");
            $query->whereRaw("agreements.is_deleted = 0");
            $query->whereRaw("contracts.archived = 0");
            $query->whereRaw("contracts.end_date = '0000-00-00 00:00:00'");
            return $query->where('practices.hospital_id', '=', $hospital->id)->distinct();
        });


        /*below where clause  of is_Deleted is added for soft delete
       Code modified_on: 11/04/2016
       */
        $practice_agreements = DB::table('practices')->select('practices.id', 'agreements.name')
            ->join("physician_practice_history", "physician_practice_history.practice_id", "=", "practices.id")
            //drop column practice_id from table 'physicians' changes by 1254 : issue fixed for practice change
            ->join("physician_practices", "physician_practices.practice_id", "=", "physician_practices.id")
            ->join("contracts", "contracts.physician_id", "=", "physician_practices.physician_id")
            ->join("agreements", function ($join) {
                $join->on("contracts.agreement_id", "=", "agreements.id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
            ->whereRaw("agreements.archived = 0")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("contracts.archived = 0")
            ->where('practices.hospital_id', '=', $hospital->id)
            ->get();


        $additionalPractices = [];

        for ($i = 0; $i < count($practiceData["items"]); $i++) {
            $idFound = 0;
            for ($j = 0; $j < count($practice_agreements); $j++) {
                // log::info("practice_agreement",array($practice_agreements));
                if ($practice_agreements[$j]->id == $practiceData["items"][$i]->id) {
                    $idFound++;
                    break;
                }
            }
            if ($idFound == 0) {
                $additionalPractices[] = $practiceData["items"][$i];
            }
        }


        for ($i = 0; $i < count($additionalPractices); $i++) {
            $data["items"][] = $additionalPractices[$i];
        }

        $data["items"] = array_unique($data["items"]);

        $data['agreements'] = $practice_agreements;
        $data['hospital'] = $hospital;
        $data['pagination'] = $practiceData["pagination"];
        $data['table'] = View::make('hospitals/_practices_table')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }


        return View::make('hospitals/practices')->with($data);
    }

    public function getCreatePractice($id)
    {
        $hospital = Hospital::findOrFail($id);
        $practiceTypes = options(PracticeType::all(), 'id', 'name');
        $states = options(State::orderBy('name')->get(), 'id', 'name');
        // $invoice_type = $hospital->invoice_type;

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        return View::make('hospitals/create_practice')->with([
            'hospital' => $hospital,
            'practiceTypes' => $practiceTypes,
            'states' => $states,
            // 'invoice_type' => $invoice_type
        ]);
    }

    public function postCreatePractice($id)
    {

        if (!is_hospital_owner($id))
            App::abort(403);

        $result = Practice::createPractice($id);
        return $result;
    }

    public function getReports($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);

        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospitalId, 1);

        $agreements = Request::input("agreements", null);
        $contractType = Request::input("contract_type", -1);
        $show_archived_flag = Request::input("show_archived");
        $show_deleted_physicians_flag = Request::input("show_deleted_physicians");

        if ($show_archived_flag == 1) {
            $status1 = true;
        } else {
            $status1 = false;
        }
        if ($show_deleted_physicians_flag == 1) {
            $show_deleted_physicians = true;
        } else {
            $show_deleted_physicians = false;
        }

        //$userData = Auth::user();
        if (is_super_user()) {
            $check = 1;
        } else {
            $check = 0;
        }

        if (!is_hospital_owner($hospital->id)) {
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

        $data = $this->query('HospitalReport', $options, function ($query, $options) use ($hospital) {
            return $query->where('hospital_id', '=', $hospital->id)
                ->where("type", "=", 1);
        });

        $contract_types = ContractType::getHospitalOptions($hospital->id, true);
        $default_contract_key = key($contract_types);

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/_reports_table')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data["contract_type"] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_types;
        $data["physicians"] = Physician::listByAgreements($agreements, $contractType, $show_deleted_physicians);
        $data['agreements'] = Agreement::getHospitalAgreementDataForReports($hospital->id, $data["contract_type"], $show_archived_flag);
        $data['check'] = $check;
        $data['showCheckbox'] = true;
        $data['isChecked'] = $status1;
        $data['showDeletedPhysicianCheckbox'] = true;
        $data['isPhysiciansShowChecked'] = $show_deleted_physicians;
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;
        $data['form'] = View::make('layouts/_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/reports')->with($data);

    }

    //code to get all contract approver list by  #1254
    public function getApprovers($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 1),
            'order' => Request::input('order', 1),
            'sort_min' => 1,
            'sort_max' => 10,
            'appends' => ['sort', 'order'],
            'field_names' => ['contract_name', 'physician_name', 'approval_level1', 'approval_level2', 'approval_level3', 'approval_level4', 'approval_level5', 'approval_level6']
        ];

        $subquery = "select concat(last_name, ', ' ,first_name)
          as approver from users where id =
          (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
          and contract_id = contracts.id and agreement_id = agreements.id and level=";

        $subquery_agreement = "select concat(last_name, ', ' ,first_name)
          as approver from users where id =
          (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
          and agreement_id = contracts.agreement_id and level=";

        //log::info("start ". date('Y-m-d H:i:s'));
        $data = $this->queryWithUnion('Contract', $options, function ($query, $options) use ($hospital, $subquery, $subquery_agreement) {
            return $query->select('contract_names.name as contract_name', 'physicians.id as physician_id', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery . '1 limit 1)) as approval_level1'), DB::raw('(' . $subquery . '2 limit 1)) as approval_level2'),
                DB::raw('(' . $subquery . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery . '4 limit 1)) as approval_level4'),
                DB::raw('(' . $subquery . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery . '6 limit 1)) as approval_level6'))
                ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                // ->join("physicians","physicians.id","=","contracts.physician_id")
                ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->where('agreements.approval_process', '=', '1')
                ->where('contracts.default_to_agreement', '=', '0')
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where('agreements.hospital_id', '=', $hospital->id)
                ->whereNull("contracts.deleted_at")
                ->union(DB::table('contracts')->select('contract_names.name as contract_name', 'physicians.id as physician_id', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery_agreement . '1 limit 1)) as approval_level1'),
                    DB::raw('(' . $subquery_agreement . '2 limit 1)) as approval_level2'),
                    DB::raw('(' . $subquery_agreement . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery_agreement . '4 limit 1)) as approval_level4'),
                    DB::raw('(' . $subquery_agreement . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery_agreement . '6 limit 1)) as approval_level6'))
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
//          ->join("physicians","physicians.id","=","contracts.physician_id")
                    ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->where('agreements.approval_process', '=', '1')
                    ->where('contracts.default_to_agreement', '=', '1')
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->where('agreements.hospital_id', '=', $hospital->id)
                    ->whereNull("contracts.deleted_at")
                    ->distinct())
                ->orderBy($options['field_names'][$options['sort'] - 1], $options['order'] == 1 ? 'asc' : 'desc')
                ->distinct();

        });

        //log::info("end ". date('Y-m-d H:i:s'));

        $data['hospital'] = $hospital;
        //$data['agreement_data'] = Hospital::approverlistForAllContracts(Auth::user()->id,$hospital->id);
        $data['table'] = View::make('hospitals/_approvers')->with($data)->render();
        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/approvers')->with($data);
    }

    public function postReports($id)
    {
        $hospital = Hospital::find($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $reportdata = HospitalReport::getReportData($hospital);
        return $reportdata;
    }

    public function getReport($hospital_id, $report_id)
    {
        //Log::info("getReport start");
        $hospital = Hospital::findOrFail($hospital_id);
        $report = $hospital->reports()->findOrFail($report_id);

        if (!is_hospital_owner($hospital->id) && !is_practice_manager())
            App::abort(403);

        $filename = hospital_report_path($hospital, $report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
        //Log::info("getReport end");
    }

    public function getDeleteReport($hospital_id, $report_id)
    {
        $hospital = Hospital::findOrFail($hospital_id);
        $report = HospitalReport::findOrFail($report_id);

        if (!is_hospital_owner($hospital->id))
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

    public function getInvoices($id)
    {
        $hospital = Hospital::findOrFail($id);

        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($id, 1);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $agreements = Request::input("agreements", null);
        $contractType = Request::input("contract_type", -1);
        $month_numbers = Request::input("month_number", null);
        $show_deleted_physicians_flag = Request::input("show_deleted_physicians");
        $start_date = array();
        $end_date = array();
        if ($agreements != null) {
            foreach ($agreements as $a_id) {
                $agreement = Agreement::findOrFail($a_id);
                $agreement_details = Agreement::getAgreementData($agreement);
                $start_date[$a_id] = $agreement_details->start_dates[$month_numbers[$a_id]];
                $end_date[$a_id] = $agreement_details->end_dates[$month_numbers[$a_id]];
            }
        }

        if ($show_deleted_physicians_flag == 1) {
            $show_deleted_physicians = true;
        } else {
            $show_deleted_physicians = false;
        }

        $report_id = HospitalReportCommand::$report_id;
        $report_filename = HospitalReportCommand::$report_filename;

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('HospitalInvoice', $options, function ($query, $options) use ($hospital) {
            return $query->where('hospital_id', '=', $hospital->id);
        });
        $contract_types = ContractType::getHospitalOptions($hospital->id);
        $default_contract_key = key($contract_types);

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/_invoices_table')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Check Request";
        $data['contract_types'] = $contract_types;
        //$data['practices']  = Practice::listByAgreements($agreements, $contractType);
        $data["physicians"] = Physician::getPhysicianData($hospital, $agreements, $contractType, $start_date, $end_date, $show_deleted_physicians);
        $data['agreements'] = Agreement::getHospitalAgreementData($hospital->id, $data["contract_type"]);
        $data['showDeletedPhysicianCheckbox'] = true;
        $data['isPhysiciansShowChecked'] = $show_deleted_physicians;
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;
        $data['form'] = View::make('layouts/_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/invoices')->with($data);
    }

    public function postInvoices($id)
    {

        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $practice_ids = Request::input('practices');
        $physician_ids = Request::input('physicians');
        $report_type = Request::input('report_type');
        $months = [];

        // if(count($practice_ids) == 0 && count($physician_ids) >0) //Old condition
        if ($practice_ids == null && $physician_ids != null) // Added by akash
        {
            /*$practice_ids= Physician::join('practices','practices.id','=','physicians.practice_id')
                ->whereIn('physicians.id',$physician_ids)
                ->where('practices.hospital_id','=',$hospital->id)
                ->withTrashed()
                ->distinct()->pluck('physicians.practice_id')->toArray();*/
            //physician to Multiple  hosptial by 1254

            //drop column practice_id from table 'physicians' changes by 1254 : codereview
            $practice_ids = PhysicianPractices::join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                ->whereIn('physician_practices.physician_id', $physician_ids)
                ->where('practices.hospital_id', '=', $hospital->id)
                ->whereRaw("physician_practices.start_date <= now()")
                ->whereRaw("physician_practices.end_date >= now()")
                ->withTrashed()
                ->distinct()
                ->orderBy("start_date", "desc")
                ->pluck('physician_practices.practice_id')->toArray();;
        }
        //if (count($agreement_ids) == 0 || count($practice_ids) == 0) { // Old condition
        if ($practice_ids == null || $physician_ids == null) { // Added by akash
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.invoice_selection_error')
            ]);
        }

        /* add for soft deleted physicians reports*/
        $active_physician_ids = Physician::whereIn('id', $physician_ids)->pluck('id');
        $deleted_physician_ids = Physician::onlyTrashed()->whereIn('id', $physician_ids)->pluck('id');

        if (count($active_physician_ids) > 0) {
            $active_physician_contract_ids = Contract::whereIn('physician_id', $active_physician_ids)
                ->whereIn('agreement_id', $agreement_ids)
                ->distinct()->pluck('id')->toArray();
        } else {
            $active_physician_contract_ids = array();
        }

        if (count($deleted_physician_ids) > 0) {
            $deleted_physician_contract_ids = Contract::onlyTrashed()
                ->join("physicians", function ($join) {
                    $join->on('physicians.id', '=', 'contracts.physician_id')
                        ->on('physicians.deleted_at', '<=', 'contracts.deleted_at');
                })
                ->whereIn('contracts.physician_id', $deleted_physician_ids)
                ->whereIn('contracts.agreement_id', $agreement_ids)
                ->distinct()->pluck('contracts.id')->toArray();
        } else {
            $deleted_physician_contract_ids = array();
        }

        $contract_ids = array_merge($active_physician_contract_ids, $deleted_physician_contract_ids);/*merged array*/
        $agreement_obj = new Agreement();
        if ($hospital->approve_all_invoices) {
            foreach ($agreement_ids as $agreement_id) {
                $agreement = Agreement::findOrFail($agreement_id);
                $agreement_data_start = $agreement_obj->getAgreementData($agreement);
                $agreement_data_end = $agreement_obj->getAgreementData($agreement);

                $date_start = $agreement_data_start->months[Request::input("agreement_{$agreement_id}_start_month")];
                $date_end = $agreement_data_start->months[Request::input("agreement_{$agreement_id}_start_month")];

                $months_start[] = $date_start->start_date;
                $months_end[] = $date_start->end_date;
                $months[] = Request::input("agreement_{$agreement_id}_start_month");
                $months[] = Request::input("agreement_{$agreement_id}_start_month");
            }
        } else {
            foreach ($agreement_ids as $agreement_id) {
                $months[] = Request::input("agreement_{$agreement_id}_start_month");
                $months[] = Request::input("agreement_{$agreement_id}_start_month");
                $months_start[] = Request::input("agreement_{$agreement_id}_start_month");
                $months_end[] = Request::input("agreement_{$agreement_id}_start_month");
            }
        }

        $amount_paid = new Amount_paid();
        // fetch data to generate report
        $data = $amount_paid->invoiceReportData($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, Request::input('contract_type'), true, $contract_ids);
        $rehab_data = $data; // Copy all the report data for all contract into rehab_data then update the variable based on payment type in code below.
        $data_is_lawson_interfaced = $data;
//        Log::info("data invoice ::", array($data));

        if (count($data) == 0) {
            return Redirect::back()->with([
                'error' => "No approved logs for display or payments are not submitted"
            ]);
        }

        //iterate data and check for is_lawson_interfaced to exclude those from the report
        $queue_invoice_pdf = false;
        $queue_invoice_is_lawson_interfaced_pdf = false;
        $queue_invoice_rehab_pdf = false;
        $breakdown_count = 0;
        foreach ($data as $key_data => $data_val) {
            foreach ($data_val["practices"] as $key_practice => $practice) {
                $rehab_data[$key_data]['practices'][$key_practice]['contract_data'] = []; // Empty the contract_data initially then push the only rehab contract data in this key.
                foreach ($practice["contract_data"] as $key_contract_data => $contract_data) {
                    if ($contract_data['is_lawson_interfaced']) {
                        unset($data[$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]);
                        $data[$key_data]['practices'][$key_practice]['contract_data'] = array_values($data[$key_data]['practices'][$key_practice]['contract_data']);
                        $queue_invoice_is_lawson_interfaced_pdf = true;
                    } else {
                        unset($data_is_lawson_interfaced[$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]);
                        $data_is_lawson_interfaced[$key_data]['practices'][$key_practice]['contract_data'] = array_values($data_is_lawson_interfaced[$key_data]['practices'][$key_practice]['contract_data']);
                        $queue_invoice_pdf = true;

                        if ($contract_data['payment_type_id'] == PaymentType::REHAB) {
                            unset($data[$key_data]['practices'][$key_practice]['contract_data'][$key_contract_data]); // Remove contract from normal data
                            $rehab_data[$key_data]['practices'][$key_practice]['contract_data'][] = $contract_data; // Add contract to rehab data
                            $queue_invoice_rehab_pdf = true;

                            if (count($data[$key_data]['practices'][$key_practice]['contract_data']) == 0) {
                                $queue_invoice_pdf = false;
                            }
                        }
                    }
                    // This will be used for dynamically increase the height of the custome invoice pdf page.
                    if ($breakdown_count == 0) {
                        $breakdown_count = count($contract_data['breakdown']);
                    }
                }
            }
        }

//        Log::info("data invoice ::", array($data));
//        Log::info("rehab_data invoice ::", array($rehab_data));
        $agreement_ids = implode(',', $agreement_ids);
        $practice_ids = implode(',', $practice_ids);
        $months = implode(',', $months);

//        if ($this->container->has('profiler'))
//        {
//            $this->container->get('profiler')->disable();
//        }

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

        if ($report_type == 2 || $report_type == 0) {
            $report_path = hospital_report_path($hospital);

            if (!File::exists($report_path)) {
                File::makeDirectory($report_path, 0777, true, true);
            };

            $time_stamp_zone = str_replace(' ', '_', $localtimeZone);
            $time_stamp_zone = str_replace('/', '', $time_stamp_zone);
            $time_stamp_zone = str_replace(':', '', $time_stamp_zone);
            $report_filename = "Invoices_" . $hospital->name . "_" . $time_stamp_zone . ".pdf";

            $customPaper = array(0, 0, 1683.78, 595.28);

            if ($queue_invoice_pdf) {

                $report_data = ["data" => $data,
                    "is_lawson_interfaced" => $queue_invoice_is_lawson_interfaced_pdf,
                    'hospital' => $hospital,
                    'localtimeZone' => $localtimeZone,
                    'invoice_notes' => [],
                    'print_all_invoice_flag' => false,
                ];

                if ($hospital->invoice_type > 0) {
                    $default_height = 800;
                    if ($breakdown_count > 7) {
                        $req_increase_in_row = $breakdown_count - 7;
                        $default_height = $default_height + ($req_increase_in_row * 20);
                    }
                    // $page_height =
                    $customPaper = array(0, 0, $default_height, 595.28);
                    $pdf = PDF::loadView('agreements/custome_invoice_pdf', $report_data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                } else {
                    $customPaper = array(0, 0, 1683.78, 595.28);
                    $pdf = PDF::loadView('agreements/invoice_pdf', $report_data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);

                }
                $hospital_invoice = new HospitalInvoice;
                $hospital_invoice->hospital_id = $hospital->id;
                $hospital_invoice->filename = $report_filename;
                $hospital_invoice->contracttype_id = 0;
                $hospital_invoice->period = mysql_date(date("m/d/Y"));
                $hospital_invoice->last_invoice_no = $data[0]["agreement_data"]["invoice_no"];
                $hospital_invoice->save();

                if ($report_type == 2) {
                    if ($queue_invoice_pdf) {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    } else {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    }
                }
            }

            if ($queue_invoice_rehab_pdf) {
                $report_data = ["data" => $rehab_data,
                    "is_lawson_interfaced" => $queue_invoice_is_lawson_interfaced_pdf,
                    'hospital' => $hospital,
                    'localtimeZone' => $localtimeZone,
                    'invoice_notes' => [],
                    'print_all_invoice_flag' => false,
                ];

                if ($hospital->invoice_type > 0) {
                    $default_height = 800;
                    if ($breakdown_count > 7) {
                        $req_increase_in_row = $breakdown_count - 7;
                        $default_height = $default_height + ($req_increase_in_row * 20);
                    }
                    // $page_height =
                    $customPaper = array(0, 0, $default_height, 595.28);
                    $pdf = PDF::loadView('agreements/custome_invoice_pdf', $report_data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                } else {
                    $customPaper = array(0, 0, 1683.78, 1500);
                    $pdf = PDF::loadView('agreements/rehab_invoice_pdf', $report_data)->setPaper($customPaper, 'portrait')->save($report_path . '/' . $report_filename);

                }
                $hospital_invoice = new HospitalInvoice;
                $hospital_invoice->hospital_id = $hospital->id;
                $hospital_invoice->filename = $report_filename;
                $hospital_invoice->contracttype_id = 0;
                $hospital_invoice->period = mysql_date(date("m/d/Y"));
                $hospital_invoice->last_invoice_no = $data[0]["agreement_data"]["invoice_no"];
                $hospital_invoice->save();

                if ($report_type == 2) {
                    if ($queue_invoice_pdf) {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    } else {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    }
                }
            }

            if ($queue_invoice_is_lawson_interfaced_pdf) {
                $last_invoice_no = $data_is_lawson_interfaced[0]["agreement_data"]["invoice_no"] + 1;
                $filepath = storage_path("signatures_" . $last_invoice_no);
                if (!File::exists($filepath)) {
                    File::makeDirectory($filepath, 0777, true, true);
                };

                $report_filename = "Interfaced_invoices_" . date('mdYhis') . ".pdf";

                if (!File::exists($report_path)) {
                    File::makeDirectory($report_path, 0777, true, true);
                };

                $report_data = ["data" => $data_is_lawson_interfaced,
                    "is_lawson_interfaced" => $queue_invoice_is_lawson_interfaced_pdf,
                    'hospital' => $hospital,
                    'localtimeZone' => $localtimeZone,
                    'invoice_notes' => [],
                    'print_all_invoice_flag' => false,
                ];

                $customPaper = array(0, 0, 1683.78, 595.28);
                $pdf = PDF::loadView('agreements/lawson_interface_invoice_pdf', $report_data)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);

                $hospital_invoice = new HospitalInvoice;
                $hospital_invoice->hospital_id = $hospital->id;
                $hospital_invoice->filename = $report_filename;
                $hospital_invoice->contracttype_id = 0;
                $hospital_invoice->period = mysql_date(date("m/d/Y"));
                $hospital_invoice->last_invoice_no = $last_invoice_no;
                $hospital_invoice->save();

                if ($report_type == 2) {
                    if ($queue_invoice_pdf) {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    } else {
                        return Redirect::back()->with([
                            'success' => Lang::get('hospitals.generate_invoice_success')
                        ]);
                    }
                }
            }

        }

        if ($report_type == 1 || $report_type == 0) {
            if ($queue_invoice_pdf) {
                Artisan::call('invoices:hospital', [
                    'hospital' => $hospital->id,
                    'contract_type' => Request::input('contract_type'),
                    'practices' => $practice_ids,
                    'agreements' => $agreement_ids,
                    'months' => $months,
                    'data' => $data,
                    'localtimeZone' => $localtimeZone
                ]);

                if (!HospitalInvoiceCommand::$success) {
                    return Redirect::back()->with([
                        'error' => Lang::get(HospitalInvoiceCommand::$message)
                    ]);
                }

                $report_id = HospitalInvoiceCommand::$report_id;
                $report_filename = HospitalInvoiceCommand::$report_filename;
            }

            if ($queue_invoice_is_lawson_interfaced_pdf) {
                Artisan::call('lawson_interfaced_invoices:hospital', [
                    'hospital' => $hospital->id,
                    'contract_type' => Request::input('contract_type'),
                    'practices' => $practice_ids,
                    'agreements' => $agreement_ids,
                    'months' => $months,
                    'data' => $data_is_lawson_interfaced,
                    'localtimeZone' => $localtimeZone
                ]);

                if (!HospitalLawsonInterfacedInvoiceCommand::$success) {
                    return Redirect::back()->with([
                        'error' => Lang::get(HospitalLawsonInterfacedInvoiceCommand::$message)
                    ]);
                }

                $report_id = HospitalLawsonInterfacedInvoiceCommand::$report_id;
                $report_filename = HospitalLawsonInterfacedInvoiceCommand::$report_filename;
            }

            if ($queue_invoice_rehab_pdf) {
                Artisan::call('rehab_invoices:hospital', [
                    'hospital' => $hospital->id,
                    'contract_type' => Request::input('contract_type'),
                    'practices' => $practice_ids,
                    'agreements' => $agreement_ids,
                    'months' => $months,
                    'data' => $rehab_data,
                    'localtimeZone' => $localtimeZone
                ]);

                if (!RehabHospitalInvoiceCommand::$success) {
                    return Redirect::back()->with([
                        'error' => Lang::get(RehabHospitalInvoiceCommand::$message)
                    ]);
                }

                $report_id = HospitalInvoiceCommand::$report_id;
                $report_filename = HospitalInvoiceCommand::$report_filename;
            }

            if ($queue_invoice_pdf || $queue_invoice_is_lawson_interfaced_pdf || $queue_invoice_rehab_pdf) {

                if ($queue_invoice_pdf) {
                    return Redirect::back()->with([
                        'success' => Lang::get(HospitalInvoiceCommand::$message),
                        'report_id' => $report_id,
                        'report_filename' => $report_filename
                    ]);
                }
                if ($queue_invoice_rehab_pdf) {
                    return Redirect::back()->with([
                        'success' => Lang::get(RehabHospitalInvoiceCommand::$message),
                        'report_id' => $report_id,
                        'report_filename' => $report_filename
                    ]);
                }
            } else {
                return Redirect::back()->with([
                    'success' => Lang::get(HospitalInvoiceCommand::$message)
                ]);
            }
        }
    }

    public function getInvoice($hospital_id, $invoice_id)
    {
        $hospital = Hospital::findOrFail($hospital_id);
        $invoice = $hospital->invoices()->findOrFail($invoice_id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $filename = hospital_report_path($hospital, $invoice);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteInvoice($hospital_id, $invoice_id)
    {
        $hospital = Hospital::findOrFail($hospital_id);
        $invoice = HospitalInvoice::findOrFail($invoice_id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        if (!$invoice->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('hospital.delete_invoice_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('hospitals.delete_invoice_success')
        ]);
    }

    public function getArchive($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $hospital->archived = true;

        if (!$hospital->save()) {
            return Redirect::back()->with(['error' => Lang::get('hospitals.archive_error')]);
        }

        return Redirect::back()->with(['success' => Lang::get('hospitals.archive_success')]);
    }

    public function getUnarchive($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $hospital->archived = false;

        if (!$hospital->save()) {
            return Redirect::back()->with(['error' => Lang::get('hospitals.unarchive_error')]);
        }

        return Redirect::back()->with(['success' => Lang::get('hospitals.unarchive_success')]);
    }

    public function getDelete($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $hospital->delete();

        foreach ($hospital->practices as $practice) {
            foreach ($practice->physicians as $physician) {
                $physician->contracts()->delete();
                $physician->logs()->delete();
                $physician->delete();
            }

            $practice->delete();
        }

        return Redirect::route('hospitals.index')->with(['success' => Lang::get('hospitals.delete_success')]);
    }

    public function getpaymentStatusReports($hospitalId)
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
                ->where("type", "=", 3);
        });

        $contract_type = ContractType::getHospitalOptions($hospital->id);
        $default_contract_key = key($contract_type);

        $data['hospital'] = $hospital;
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;

        $data['table'] = View::make('hospitals/paymentStatus/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_type;
        $data["physicians"] = Physician::getPhysicianData($hospital, $agreements, $contractType, $start_date, $end_date, $show_deleted_physicians);
        $data['agreements'] = Agreement::getHospitalAgreementDataForReports($hospital->id, $data["contract_type"]);
        $data['showDeletedPhysicianCheckbox'] = true;
        $data['isPhysiciansShowChecked'] = $show_deleted_physicians;
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

        return View::make('hospitals/paymentStatus/index')->with($data);
    }

    public function postpaymentStatusReports($hospitalId)
    {
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

        // log::info("local time zone",array($localtimeZone));

        $hospital = Hospital::findOrFail($hospitalId);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');
        $months = [];
        $months_start = [];
        $months_end = [];

        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { // Old condition changed by akash
        if ($agreement_ids == null || $physician_ids == null) { // Newly added condition because null was coming in the variable.
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

        $data = $physician_logs->logReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, Request::input('contract_type'), $localtimeZone, 'status');
        //$data['local'] =$localtimeZone;
        // log::info("data",array($data));

        $agreement_ids = implode(',', $agreement_ids);
        $physician_ids = implode(',', $physician_ids);
        $months = implode(',', $months);
        $months_split = implode(',', $months_split);

        //Allow all users to select multiple months for log report
        Artisan::call('reports:paymentstatus', [
            'hospital' => $hospital->id,
            'contract_type' => Request::input('contract_type'),
            'physicians' => $physician_ids,
            'agreements' => $agreement_ids,
            'months' => $months_split,
            "report_data" => $data
        ]);

        if (!PaymentStatusReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PaymentStatusReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PaymentStatusReport::$message),
            'report_id' => PaymentStatusReport::$report_id,
            'report_filename' => PaymentStatusReport::$report_filename
        ]);
    }


    public function getAmendedContracts()
    {


        $items['items'] = DB::select("call sp_contracts_updated_by_hospital_based_userid('" . $this->currentUser->id . "','30')");

        $data['table'] = View::make('hospitals/_amended_contracts_table')->with($items)->render();
        $data['items_count'] = count($items['items']);

        return View::make('hospitals/amended_contracts')->with($data);
    }

    public function getExpiringContracts()
    {


        if (!is_super_user()) {
            $options = [
                'sort' => Request::input('sort', 6),
                'order' => Request::input('order', 1),
                'sort_min' => 1,
                'sort_max' => 6,
                'appends' => ['sort', 'order'],
                'field_names' => ['hospital_name', 'agreement_name', 'contract_name', 'last_name', 'start_date', 'manual_contract_end_date', 'first_name']
            ];
            $data = $this->query('Contract', $options, function ($query, $options) {
                $day_after_90_days = date('Y-m-d', strtotime("+90 days"));
                $query = $query->select("hospitals.name as hospital_name", "agreements.name as agreement_name", "contract_names.name as contract_name", "physicians.last_name as last_name", "agreements.start_date as start_date", "contracts.manual_contract_end_date as manual_contract_end_date", "physicians.first_name as first_name")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.archived=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.end_date", "<=", $day_after_90_days)
                    ->where("agreements.hospital_id", "<>", 45)
                    ->where("hospital_user.user_id", "=", $this->currentUser->id)
                    ->whereNull('contracts.deleted_at')->distinct();
                return $query;
            });
        } else {
            if (Request::input('id')) {
                Session::put('id', Request::input('id'));
            }
            $options = [
                'sort' => Request::input('sort', 6),
                'order' => Request::input('order', 1),
                'sort_min' => 1,
                'sort_max' => 6,
                'appends' => ['sort', 'order', 'id'],
                'field_names' => ['hospital_name', 'agreement_name', 'contract_name', 'last_name', 'start_date', 'manual_contract_end_date', 'first_name'],
                'id' => Session::get('id')
            ];
            $data = $this->query('Contract', $options, function ($query, $options) {
                $day_after_90_days = date('Y-m-d', strtotime("+90 days"));
                $last_month = date('Y-m-d', strtotime("first day of last month"));
                $query = $query->select("hospitals.name as hospital_name", "agreements.name as agreement_name", "contract_names.name as contract_name", "physicians.last_name as last_name", "agreements.start_date as start_date", "contracts.manual_contract_end_date as manual_contract_end_date", "physicians.first_name as first_name")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.archived=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->where("agreements.end_date", ">=", $last_month)
                    ->where("agreements.end_date", "<=", $day_after_90_days)
                    ->where("agreements.hospital_id", "<>", 45)
                    ->where("agreements.hospital_id", "=", $options['id'])
                    ->whereNull('contracts.deleted_at')->distinct();
                return $query;
            });
        }
        $data['table'] = View::make('hospitals/_expiring_contracts_table')->with($data)->render();
        return View::make('hospitals/expiring_contracts')->with($data);
    }

    public function interfaceDetails($id)
    {

        $interfaceDetailsImageNow = HospitalInterfaceImageNow::where('hospital_id', $id)->whereNull('deleted_at')->first();
        if (!$interfaceDetailsImageNow) {
            $interfaceDetailsImageNow = new HospitalInterfaceImageNow();
        } else {
            $data['interfaceType'] = 2;
        }

        $interfaceDetailsLawson = HospitalInterfaceLawson::where('hospital_id', $id)->whereNull('deleted_at')->first();
        $data['interfaceType'] = 0;
        if (!$interfaceDetailsLawson) {
            $interfaceDetailsLawson = new HospitalInterfaceLawson();
        } else {
            $data['interfaceType'] = 1;
        }

        if ($data['interfaceType'] == 0) {
            $data['interfaceType'] = 1;
        }

        $data['hospital'] = Hospital::findOrFail($id);
        $data['interfaceDetailsLawson'] = $interfaceDetailsLawson;
        $data['interfaceDetailsImageNow'] = $interfaceDetailsImageNow;
        //USED TO CONTROL WHICH INTERFACE TYPES ARE AVAILABLE ON THE PHYSICIAN INTERFACE DETAILS FORM
        $data['interfaceTypes'] = InterfaceType::whereIn('id', [1, 2])->pluck('name', 'id');

        return View::make('hospitals/interfacedetails')->with($data);
    }

    public function postInterfaceDetails($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new HospitalInterfaceValidation();
        $emailvalidation = new EmailValidation();
        $interfaceType = Request::input("interface_type_id");

        if ($interfaceType == 1) {
            $hospitalInterface = HospitalInterfaceLawson::where('hospital_id', $id)->whereNull('deleted_at')->first();
            if ($hospitalInterface) {

                if (!$validation->validateEdit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }
                $hospitalInterface->protocol = Request::input("protocol");
                $hospitalInterface->host = Request::input("host");
                $hospitalInterface->port = Request::input("port");
                $hospitalInterface->username = Request::input("username");
                $hospitalInterface->password = Request::input("password");
                $hospitalInterface->apcinvoice_filename = Request::input("apcinvoice_filename");
                $hospitalInterface->apcdistrib_filename = Request::input("apcdistrib_filename");
                $hospitalInterface->api_username = Request::input("api_username");
                $hospitalInterface->api_password = Request::input("api_password");
                $hospitalInterface->updated_by = $this->currentUser->id;
                if (!$hospitalInterface->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not update')]);
                } else {
                    return Redirect::route('hospitals.edit', $hospital->id)
                        ->with(['success' => Lang::get('hospitals.edit_success')]);
                }
            } else {

                if (!$validation->validateCreate(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }

                $hospitalInterfacenew = new HospitalInterfaceLawson();
                $hospitalInterfacenew->hospital_id = $hospital->id;
                $hospitalInterfacenew->protocol = Request::input("protocol");
                $hospitalInterfacenew->host = Request::input("host");
                $hospitalInterfacenew->port = Request::input("port");
                $hospitalInterfacenew->username = Request::input("username");
                $hospitalInterfacenew->password = Request::input("password");
                $hospitalInterfacenew->apcinvoice_filename = Request::input("apcinvoice_filename");
                $hospitalInterfacenew->apcdistrib_filename = Request::input("apcdistrib_filename");
                $hospitalInterfacenew->api_username = Request::input("api_username");
                $hospitalInterfacenew->api_password = Request::input("api_password");
                $hospitalInterfacenew->created_by = $this->currentUser->id;
                $hospitalInterfacenew->updated_by = $this->currentUser->id;
                if (!$hospitalInterfacenew->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not save')]);
                } else {
                    return Redirect::route('hospitals.edit', $hospital->id)
                        ->with(['success' => Lang::get('hospitals.edit_success')]);
                }
            }
        } elseif ($interfaceType == 2) {
            $hospitalInterface = HospitalInterfaceImageNow::where('hospital_id', $id)->whereNull('deleted_at')->first();
            if ($hospitalInterface) {

                if (!$validation->validateEdit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }
                $hospitalInterface->protocol = Request::input("protocol_imagenow");
                $hospitalInterface->host = Request::input("host_imagenow");
                $hospitalInterface->port = Request::input("port_imagenow");
                $hospitalInterface->username = Request::input("username_imagenow");
                $hospitalInterface->password = Request::input("password_imagenow");
                $hospitalInterface->email = Request::input("email");
                $hospitalInterface->api_username = Request::input("api_username_imagenow");
                $hospitalInterface->api_password = Request::input("api_password_imagenow");
                $hospitalInterface->updated_by = $this->currentUser->id;
                if (!$hospitalInterface->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not update')]);
                } else {
                    return Redirect::route('hospitals.edit', $hospital->id)
                        ->with(['success' => Lang::get('hospitals.edit_success')]);
                }
            } else {

                if (!$validation->validateCreate(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                if (!$emailvalidation->validateEmailDomain(Request::input())) {
                    return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
                }

                $hospitalInterfacenew = new HospitalInterfaceImageNow();
                $hospitalInterfacenew->hospital_id = $hospital->id;
                $hospitalInterfacenew->protocol = Request::input("protocol_imagenow");
                $hospitalInterfacenew->host = Request::input("host_imagenow");
                $hospitalInterfacenew->port = Request::input("port_imagenow");
                $hospitalInterfacenew->username = Request::input("username_imagenow");
                $hospitalInterfacenew->password = Request::input("password_imagenow");
                $hospitalInterfacenew->email = Request::input("email");
                $hospitalInterfacenew->api_username = Request::input("api_username_imagenow");
                $hospitalInterfacenew->api_password = Request::input("api_password_imagenow");
                $hospitalInterfacenew->created_by = $this->currentUser->id;
                $hospitalInterfacenew->updated_by = $this->currentUser->id;
                if (!$hospitalInterfacenew->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not save')]);
                } else {
                    return Redirect::route('hospitals.edit', $hospital->id)
                        ->with(['success' => Lang::get('hospitals.edit_success')]);
                }
            }
        }

    }

    public function getIsLawsonInterfacedContracts()
    {


        if (!is_super_user()) {
            $options = [
                'sort' => Request::input('sort', 1),
                'order' => Request::input('order', 1),
                'sort_min' => 1,
                'sort_max' => 1,
                'appends' => ['sort', 'order'],
                'field_names' => ['last_name', 'first_name', 'agreement_name', 'contract_name', 'start_date', 'end_date', 'invoice_company', 'invoice_vendor', 'invoice_process_level', 'distrib_company', 'distrib_accounting_unit', 'distrib_account', 'distrib_sub_account']
            ];
            $data = $this->query('Contract', $options, function ($query, $options) {
                $query = $query->select("physicians.last_name as last_name", "physicians.first_name as first_name", "agreements.name as agreement_name", "contract_names.name as contract_name", "agreements.start_date as start_date", "contracts.manual_contract_end_date as end_date", "physician_interface_lawson_apcinvoice.cvi_company as invoice_company", "physician_interface_lawson_apcinvoice.cvi_vendor as invoice_vendor", "physician_interface_lawson_apcinvoice.cvi_proc_level as invoice_process_level", "contract_interface_lawson_apcdistrib.cvd_dist_company as distrib_company", "contract_interface_lawson_apcdistrib.cvd_dis_acct_unit as distrib_accounting_unit", "contract_interface_lawson_apcdistrib.cvd_dis_account as distrib_account", "contract_interface_lawson_apcdistrib.cvd_dis_sub_acct as distrib_sub_account")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->join("physician_interface_lawson_apcinvoice", "physician_interface_lawson_apcinvoice.physician_id", "=", "physicians.id")
                    ->join("contract_interface_lawson_apcdistrib", "contract_interface_lawson_apcdistrib.contract_id", "=", "contracts.id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.archived=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.hospital_id", "<>", 45)
                    ->where("hospital_user.user_id", "=", $this->currentUser->id)
                    ->where("contracts.is_lawson_interfaced", "=", true)
                    ->whereNull('contracts.deleted_at');
                return $query;
            });
        } else {
            if (Request::input('id')) {
                Session::put('id', Request::input('id'));
            }
            $options = [
                'sort' => Request::input('sort', 1),
                'order' => Request::input('order', 1),
                'sort_min' => 1,
                'sort_max' => 1,
                'appends' => ['sort', 'order', 'id'],
                'field_names' => ['last_name', 'first_name', 'agreement_name', 'contract_name', 'start_date', 'end_date', 'invoice_company', 'invoice_vendor', 'invoice_process_level', 'distrib_company', 'distrib_accounting_unit', 'distrib_account', 'distrib_sub_account'],
                'id' => Session::get('id')
            ];
            $data = $this->query('Contract', $options, function ($query, $options) {
                $query = $query->select("physicians.last_name as last_name", "physicians.first_name as first_name", "agreements.name as agreement_name", "contract_names.name as contract_name", "agreements.start_date as start_date", "contracts.manual_contract_end_date as end_date", "physician_interface_lawson_apcinvoice.cvi_company as invoice_company", "physician_interface_lawson_apcinvoice.cvi_vendor as invoice_vendor", "physician_interface_lawson_apcinvoice.cvi_proc_level as invoice_process_level", "contract_interface_lawson_apcdistrib.cvd_dist_company as distrib_company", "contract_interface_lawson_apcdistrib.cvd_dis_acct_unit as distrib_accounting_unit", "contract_interface_lawson_apcdistrib.cvd_dis_account as distrib_account", "contract_interface_lawson_apcdistrib.cvd_dis_sub_acct as distrib_sub_account")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->join("physician_interface_lawson_apcinvoice", "physician_interface_lawson_apcinvoice.physician_id", "=", "physicians.id")
                    ->join("contract_interface_lawson_apcdistrib", "contract_interface_lawson_apcdistrib.contract_id", "=", "contracts.id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.archived=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("agreements.hospital_id", "<>", 45)
                    ->where("agreements.hospital_id", "=", $options['id'])
                    ->where("contracts.is_lawson_interfaced", "=", true)
                    ->whereNull('contracts.deleted_at');
                return $query;
            });
        }
        $data['table'] = View::make('hospitals/_is_lawson_interfaced_contracts_table')->with($data)->render();
        return View::make('hospitals/is_lawson_interfaced_contracts')->with($data);
    }

    public function getLawsonInterfaceReports($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);

        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospitalId, 1);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);


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
                ->where("type", "=", 4);
        });

        $interface_dates = InterfaceTankLawson::getInterfaceDates($hospital->id);
        $default_key = key($interface_dates);

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/lawsonInterface/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['form_title'] = "Generate Report";
        $data["interface_date"] = Request::input("interface_date", $default_key);
        $data['interface_dates'] = $interface_dates;
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;


        //Allow all users to select multiple months for log report
        $data['form'] = View::make('layouts/_reports_form_lawson_interface')->with($data)->render();

        /*if(is_super_user()) {
            $data['form'] = View::make('layouts/_reports_form_multiple_months')->with($data)->render();
        }
        else{
            $data['form'] = View::make('layouts/_reports_form')->with($data)->render();
        }*/

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/lawsonInterface/index')->with($data);

    }

    public function postLawsonInterfaceReports($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $interface_date = Request::input('interface_date');
        // Log::Info('inside postLawsonInterfaceReports', array($interface_date));

        // if(count($interface_date )==0){ // Old condition changed by akash for null object issue fix
        if (!$interface_date) {
            return Redirect::back()->with([
                'error' => Lang::get('lawson_interface.selection_error')
            ]);
        }

        $interface_tank = new InterfaceTankLawson();
        $data = $interface_tank->getReportData($hospital, $interface_date);

        //Allow all users to select multiple months for log report
        Artisan::call('reports:hospital_lawson_interfaced', [
            'hospital' => $hospital->id,
            'interface_date' => $interface_date,
            "report_data" => $data
        ]);

        if (!HospitalLawsonInterfacedReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HospitalLawsonInterfacedReportCommand::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(HospitalLawsonInterfacedReportCommand::$message),
            'report_id' => HospitalLawsonInterfacedReportCommand::$report_id,
            'report_filename' => HospitalLawsonInterfacedReportCommand::$report_filename
        ]);
    }

    public function updateInvoiceDisplayStatus()
    {
        $status = Request::input('status');
        $user_id = Request::input('user_id');
        $result = Hospital::update_invoice_display_status($user_id, $status);
        return $result;
    }


    public function getMassWelcomeEmailer($id)
    {
        $hospital = Hospital::findOrFail($id);
        //get physicians count
        $physicians_count = Physician::
        //drop column practice_id from table 'physicians' changes by 1254
        join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            // join('practices','practices.id','=','physicians.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->where('hospitals.id', '=', $id)
            ->whereRaw('physician_practices.start_date <= now()')
            ->whereRaw('physician_practices.end_date >= now()')
            ->whereNull('physician_practices.deleted_at')
            ->whereNull('physicians.deleted_at')
            ->count();
        //get users count
        $users_count = User::
        join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->join('hospitals', 'hospitals.id', '=', 'hospital_user.hospital_id')
            ->where('hospitals.id', '=', $id)
            ->whereNull('users.deleted_at')
            ->count();
        //get pms count
        $pms_count = User::
        join('practice_user', 'practice_user.user_id', '=', 'users.id')
            ->join('practices', 'practices.id', '=', 'practice_user.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->where('hospitals.id', '=', $id)
            ->whereNull('users.deleted_at')
            ->count();

        $data = [];
        $data['hospital'] = $hospital;
        $data['user_types'][6] = 'Physicians - Count: ' . $physicians_count;
        $data['user_types'][2] = 'Users - Count: ' . $users_count;
        $data['user_types'][3] = 'Practice Managers - Count: ' . $pms_count;
        $data['user_type'] = 2;
        return View::make('hospitals/masswelcomeemailer')->with($data);
    }

    public function postMassWelcomeEmailer()
    {
        $hospital_id = Request::input('hospital_id');
        $user_type = Request::input('user_type');
        switch ($user_type) {
            case 2:
                $users = User::
                select('users.id')
                    ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
                    ->join('hospitals', 'hospitals.id', '=', 'hospital_user.hospital_id')
                    ->where('hospitals.id', '=', $hospital_id)
                    ->whereNull('users.deleted_at')
                    ->get();
                foreach ($users as $user) {
                    $this->sendWelcomeUser($user->id);
                }
                break;
            case 3:
                $pms = User::
                select('users.id')
                    ->join('practice_user', 'practice_user.user_id', '=', 'users.id')
                    ->join('practices', 'practices.id', '=', 'practice_user.practice_id')
                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->where('hospitals.id', '=', $hospital_id)
                    ->whereNull('users.deleted_at')
                    ->get();
                foreach ($pms as $pm) {
                    $this->sendWelcomeUser($pm->id);
                }
                break;
            case 6:
                $physicians = Physician::
                select('physicians.id')

                    //drop column practice_id from table 'physicians' changes by 1254
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->where('hospitals.id', '=', $hospital_id)
                    ->whereRaw('physician_practices.start_date <= now()')
                    ->whereRaw('physician_practices.end_date >= now()')
                    ->whereNull('physician_practices.deleted_at')
                    ->whereNull('physicians.deleted_at')
                    ->get();
                foreach ($physicians as $physician) {
                    $this->sendWelcomePhysician($physician->id, $hospital_id);
                }
                break;
        }
        return Redirect::route('hospitals.edit', $hospital_id)
            ->with([
                'success' => Lang::get('hospitals.mass_welcome_emailer_success')
            ]);
    }

    public function sendWelcomeUser($id)
    {
        $user = User::findOrFail($id);
        $hospital_names = array();
        foreach ($user->hospitals as $hospital) {
            $hospital_names[] = $hospital->name;
        }
        $data = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'password' => $user->password_text,
            'hospitals_name' => $hospital_names,
            'type' => EmailSetup::USER_WELCOME,
            'with' => [
                'name' => "{$user->first_name} {$user->last_name}",
                'email' => $user->email,
                'password' => $user->password_text,
                'hospitals_name' => $hospital_names
            ]
        ];

        EmailQueueService::sendEmail($data);

        return true;
    }

    public function sendWelcomePhysician($id, $hospital_id)
    {
        $physician = Physician::findOrFail($id);
        //$practice = $physician->practice;
        $hospital = Hospital::findOrFail($hospital_id);
        $practice = PhysicianPractices::select("physician_practices.practice_id", "practices.name")
            ->join("practices", "practices.hospital_id", "=", "physician_practices.hospital_id")
            ->where("physician_practices.hospital_id", "=", $hospital_id)
            ->where("physician_practices.physician_id", "=", $id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            ->orderBy("physician_practices.start_date", "desc")
            ->first();

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $data = [
            'name' => "{$physician->first_name} {$physician->last_name}",
            'email' => $physician->email,
            'password' => $physician->password_text,
            'practice' => $practice->name,
            'hospital' => $hospital->name,
            'type' => EmailSetup::PHYSICIAN_WELCOME,
            'with' => [
                'name' => "{$physician->first_name} {$physician->last_name}",
                'hospital' => $hospital->name,
                'email' => $physician->email,
                'password' => $physician->password_text
            ]
        ];

        EmailQueueService::sendEmail($data);

        return true;
    }

    public function getActiveContractReports($hospitalId)
    {
        $hospital = Hospital::findOrFail($hospitalId);

        if (!is_hospital_owner($hospital->id))
            App::abort(403);

        $isLawsonInterfaceReady = $hospital->get_isInterfaceReady($hospitalId, 1);

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
                ->where("type", "=", 5);
        });

        $data['hospital'] = $hospital;
        $data['table'] = View::make('hospitals/activeContracts/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['form_title'] = "Generate Report";
        $data['isLawsonInterfaceReady'] = $isLawsonInterfaceReady;


        //Allow all users to select multiple months for log report
        $data['form'] = View::make('layouts/_reports_form_active_contracts')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('hospitals/activeContracts/index')->with($data);

    }

    public function postActiveContractReports($id)
    {
        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        // log::info("timestamp",array($timestamp));
        // log::info("timeZone",array($timeZone));

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

        //log::info("active contract local time zone",array($localtimeZone));

        $user_id = Auth::user()->id;
        $group_id = Auth::user()->group_id;

        $facility = $id;


        /*Contracts data finding*/
        $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), "physicians.email as physician_email", "contract_types.name as contract_type",
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->where("hospitals.id", "=", $facility);

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('contracts.deleted_at');

        $contract_list = $contract_list->orderBy('hospital_name')->orderBy('agreement_start_date')->groupBy('contracts.id')->get();

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));

        $filter_facility = '';
        foreach ($contract_list as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                $end_date = $last_date_of_prev_month;
            } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date'])) {
                $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
            } else {
                $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
            }
            $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
            //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();

            $hospital = Hospital::findOrFail($id);

            $subquery = "select concat(last_name, ', ' ,first_name)
                    as approver from users where id =
                    (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
                    and contract_id = contracts.id and agreement_id = agreements.id and level=";

            $subquery_email = "select email as email from users where id =
                    (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
                    and contract_id = contracts.id and agreement_id = agreements.id and level=";

            $subquery_agreement = "select concat(last_name, ', ' ,first_name)
                    as approver from users where id =
                    (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
                    and agreement_id = contracts.agreement_id and level=";

            $subquery_agreement_email = "select email as email from users where id =
                    (select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
                    and agreement_id = contracts.agreement_id and level=";

            $approval_levels = Contract::select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id',
                DB::raw('(' . $subquery . '1 limit 1)) as approval_level1'), DB::raw('(' . $subquery . '2 limit 1)) as approval_level2'),
                DB::raw('(' . $subquery . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery . '4 limit 1)) as approval_level4'),
                DB::raw('(' . $subquery . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery . '6 limit 1)) as approval_level6'),
                DB::raw('(' . $subquery_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_email . '2 limit 1)) as approval_level_email2'),
                DB::raw('(' . $subquery_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_email . '4 limit 1)) as approval_level_email4'),
                DB::raw('(' . $subquery_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_email . '6 limit 1)) as approval_level_email6'))
                ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->where('agreements.approval_process', '=', '1')
                ->where('contracts.default_to_agreement', '=', '0')
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where('agreements.hospital_id', '=', $hospital->id)
                ->whereNull("contracts.deleted_at")
                ->union(DB::table('contracts')->select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery_agreement . '1 limit 1)) as approval_level1'),
                    DB::raw('(' . $subquery_agreement . '2 limit 1)) as approval_level2'),
                    DB::raw('(' . $subquery_agreement . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery_agreement . '4 limit 1)) as approval_level4'),
                    DB::raw('(' . $subquery_agreement . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery_agreement . '6 limit 1)) as approval_level6'),
                    DB::raw('(' . $subquery_agreement_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_agreement_email . '2 limit 1)) as approval_level_email2'),
                    DB::raw('(' . $subquery_agreement_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_agreement_email . '4 limit 1)) as approval_level_email4'),
                    DB::raw('(' . $subquery_agreement_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_agreement_email . '6 limit 1)) as approval_level_email6'))
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->where('agreements.approval_process', '=', '1')
                    ->where('contracts.default_to_agreement', '=', '1')
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->where('agreements.hospital_id', '=', $hospital->id)
                    ->whereNull("contracts.deleted_at"))
                ->distinct()
                ->get();

            $approval_level1 = "";
            $approval_level2 = "";
            $approval_level3 = "";
            $approval_level4 = "";
            $approval_level5 = "";
            $approval_level6 = "";

            $approval_level_email1 = "";
            $approval_level_email2 = "";
            $approval_level_email3 = "";
            $approval_level_email4 = "";
            $approval_level_email5 = "";
            $approval_level_email6 = "";

            foreach ($approval_levels as $approval_level) {
                if ($contract->id == $approval_level["contract_id"]) {
                    $approval_level1 = $approval_level->approval_level1;
                    $approval_level2 = $approval_level->approval_level2;
                    $approval_level3 = $approval_level->approval_level3;
                    $approval_level4 = $approval_level->approval_level4;
                    $approval_level5 = $approval_level->approval_level5;
                    $approval_level6 = $approval_level->approval_level6;

                    $approval_level_email1 = $approval_level->approval_level_email1;
                    $approval_level_email2 = $approval_level->approval_level_email2;
                    $approval_level_email3 = $approval_level->approval_level_email3;
                    $approval_level_email4 = $approval_level->approval_level_email4;
                    $approval_level_email5 = $approval_level->approval_level_email5;
                    $approval_level_email6 = $approval_level->approval_level_email6;
                }
            }

            // Below code is added to show the practice name in place of physician if the contract is shared.
            $check_multiple_physician = PhysicianContracts::select('physician_contracts.*', 'physicians.email as physician_email', 'practices.name as practice_name')
                ->join('practices', 'practices.id', '=', 'physician_contracts.practice_id')
                ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
                ->where('physician_contracts.contract_id', '=', $contract->id)
                ->whereNull('physicians.deleted_at')
                ->whereNull('physician_contracts.deleted_at')
                ->get();

            $physician_email = "";
            $physician_name = "";
            if (count($check_multiple_physician) > 1) {
                $practice_obj = $check_multiple_physician->first();
                $physician_name = $practice_obj->practice_name;

                foreach ($check_multiple_physician as $key => $check_multiple_physician_obj) {
                    if (($key + 1) < count($check_multiple_physician) && ($key + 1) != count($check_multiple_physician)) {
                        $physician_email .= $check_multiple_physician_obj->physician_email . ', ';
                    } else {
                        $physician_email .= $check_multiple_physician_obj->physician_email;
                    }
                }
            } else {
                $physician_name = $contract->physician_name;
                $physician_email = $contract->physician_email;
            }
            // practice name change ends here.

            $data[] = [
                "contract_name" => $contract->contract_name,
                "contract_type" => $contract->contract_type,
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $physician_name,
                "physician_email" => $physician_email,
                "agreement_start_date" => format_date($contract->agreement_start_date),
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                "amount" => $total_amount_paid->paid != null ? $total_amount_paid->paid : 0,
                "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                "localtimeZone" => $localtimeZone,
                "approval_level1" => $approval_level1 != "" ? $approval_level1 : "-",
                "approval_level2" => $approval_level2 != "" ? $approval_level2 : "-",
                "approval_level3" => $approval_level3 != "" ? $approval_level3 : "-",
                "approval_level4" => $approval_level4 != "" ? $approval_level4 : "-",
                "approval_level5" => $approval_level5 != "" ? $approval_level5 : "-",
                "approval_level6" => $approval_level6 != "" ? $approval_level6 : "-",

                "approval_level_email1" => $approval_level_email1 != "" ? $approval_level_email1 : "",
                "approval_level_email2" => $approval_level_email2 != "" ? $approval_level_email2 : "",
                "approval_level_email3" => $approval_level_email3 != "" ? $approval_level_email3 : "",
                "approval_level_email4" => $approval_level_email4 != "" ? $approval_level_email4 : "",
                "approval_level_email5" => $approval_level_email5 != "" ? $approval_level_email5 : "",
                "approval_level_email6" => $approval_level_email6 != "" ? $approval_level_email6 : ""

            ];
            $filter_facility = $contract->hospital_name;
        }

        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;


        Artisan::call("reports:HospitalActiveContractsReport", [
            "data" => $data,
            "user_id" => $user_id,
            "filter_facility" => $filter_facility,
            "hospital_id" => $id
        ]);

        if (!HospitalActiveContractsReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HospitalActiveContractsReportCommand::$message)
            ]);
        }

        $report_id = HospitalActiveContractsReportCommand::$report_id;
        $report_filename = HospitalActiveContractsReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HospitalActiveContractsReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    public function getDutiesManagement($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $data = DutyManagement::getDutiesManagement($id);
        $data['hospital'] = $hospital;
        return View::make('hospitals/dutiesmanagement')->with($data);
    }

    public function postDutiesManagement($id)
    {
        $hospital = Hospital::findOrFail($id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $data = DutyManagement::postDutiesManagement($id);

        return $data;
    }

    public function getDashboardForRehabAdmin()
    {
        return View::make('dashboard/rehab_admin');
    }

    public function getRehabWeeklyMax()
    {

        $data = [];
        $options = [
            'filter' => Request::input('filter', 1),
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['contract_id', 'physician_name', 'contract_type_name', 'contract_name', 'hospital_name'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        $user_id_with_proxy_ids = LogApproval::find_proxy_aaprovers(Auth::user()->id);

        if (count($user_id_with_proxy_ids) > 0) {
            $agreement_ids = Agreement::select('agreements.id as id')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->whereIn('agreement_approval_managers_info.user_id', $user_id_with_proxy_ids) //added this condition for checking with proxy approvers
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->where('agreements.is_Deleted', '=', '0')
                ->orderBy('agreements.payment_frequency_type')
                ->distinct()
                ->pluck("id")->toArray();

            $data = $this->query('Contract', $options, function ($query, $options) use ($agreement_ids) {
                $query = $query->select('contracts.id as contract_id',
                    DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
                    'contract_types.name as contract_type_name', 'contract_names.name as contract_name',
                    'hospitals.name as hospital_name', 'agreements.name as agreement_name')
                    ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join('physician_practices', function ($join) {
                        $join->on('physician_practices.physician_id', '=', 'physician_contracts.physician_id');
                        $join->on('physician_practices.practice_id', '=', 'physician_contracts.practice_id');

                    })
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                    ->whereNull("contracts.deleted_at")
                    ->whereRaw("contracts.archived = false")
                    ->whereRaw("contracts.payment_type_id = 9")
                    ->whereRaw("physician_contracts.physician_id = physician_practices.physician_id")
                    ->whereNull("physician_practices.deleted_at")
                    ->whereRaw("physician_practices.start_date <= now()")
                    ->whereRaw("physician_practices.end_date >= now()")
                    ->whereIn('contracts.agreement_id', $agreement_ids)
                    ->groupBy('physician_contracts.contract_id');

                return $query;
            });
        }
        foreach ($data['items'] as $contract) {
            $contract->physician_name = '';
            $temp_physician_contracts = PhysicianContracts::where('contract_id', '=', $contract->contract_id)->whereNull('deleted_at')->get();

            if (count($temp_physician_contracts) > 0) {
                foreach ($temp_physician_contracts as $key => $physician_contract_obj) {
                    $physician_obj = Physician::select(
                        DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),)->where('id', '=', $physician_contract_obj->physician_id)->first();
                    if ($physician_obj) {
                        if (empty($contract->physician_name) && $key == 0) {
                            if (count($temp_physician_contracts) == ($key + 1)) {
                                $contract->physician_name .= $physician_obj->physician_name;
                            } else {
                                $contract->physician_name .= $physician_obj->physician_name . ', ';
                            }
                        } else if (count($temp_physician_contracts) == ($key + 1)) {
                            $contract->physician_name .= $physician_obj->physician_name;
                        } else {
                            $contract->physician_name .= ', ' . $physician_obj->physician_name . ', ';
                        }
                    }
                }
            }
        }

        $data['table'] = View::make('dashboard/partials/weekly_max_table')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('dashboard/rehab_weekly_max')->with($data);
    }

    public function getRehabAdminHours()
    {

        $data = [];
        $options = [
            'filter' => Request::input('filter', 1),
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['contract_id', 'physician_name', 'contract_type_name', 'contract_name', 'hospital_name'],
            'per_page' => 9999 // This is needed to allow the table paginator to work.
        ];

        $user_id_with_proxy_ids = LogApproval::find_proxy_aaprovers(Auth::user()->id);

        if (count($user_id_with_proxy_ids) > 0) {
            $agreement_ids = Agreement::select('agreements.id as id')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->whereIn('agreement_approval_managers_info.user_id', $user_id_with_proxy_ids) //added this condition for checking with proxy approvers
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->where('agreements.is_Deleted', '=', '0')
                ->orderBy('agreements.payment_frequency_type')
                ->distinct()
                ->pluck("id")->toArray();

            $data = $this->query('Contract', $options, function ($query, $options) use ($agreement_ids) {
                $query = $query->select('contracts.id as contract_id',
                    DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
                    'contract_types.name as contract_type_name', 'contract_names.name as contract_name',
                    'hospitals.name as hospital_name', 'agreements.name as agreement_name')
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                    ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                    ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                    ->join('physician_practices', function ($join) {
                        $join->on('physician_practices.physician_id', '=', 'physician_contracts.physician_id');
                        $join->on('physician_practices.practice_id', '=', 'physician_contracts.practice_id');

                    })
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                    ->whereNull("contracts.deleted_at")
                    ->whereRaw("contracts.archived = false")
                    ->whereRaw("contracts.payment_type_id = 9")
                    ->whereRaw("physician_contracts.physician_id = physician_practices.physician_id")
                    ->whereNull("physician_practices.deleted_at")
                    ->whereRaw("physician_practices.start_date <= now()")
                    ->whereRaw("physician_practices.end_date >= now()")
                    ->whereIn('contracts.agreement_id', $agreement_ids)
                    ->groupBy('physician_contracts.contract_id');

                return $query;
            });
        }

        foreach ($data['items'] as $contract) {
            $contract->physician_name = '';
            $temp_physician_contracts = PhysicianContracts::where('contract_id', '=', $contract->contract_id)->whereNull('deleted_at')->get();

            if (count($temp_physician_contracts) > 0) {
                foreach ($temp_physician_contracts as $key => $physician_contract_obj) {
                    $physician_obj = Physician::select(
                        DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),)->where('id', '=', $physician_contract_obj->physician_id)->first();
                    if ($physician_obj) {
                        if (empty($contract->physician_name) && $key == 0) {
                            if (count($temp_physician_contracts) == ($key + 1)) {
                                $contract->physician_name .= $physician_obj->physician_name;
                            } else {
                                $contract->physician_name .= $physician_obj->physician_name . ', ';
                            }
                        } else if (count($temp_physician_contracts) == ($key + 1)) {
                            $contract->physician_name .= $physician_obj->physician_name;
                        } else {
                            $contract->physician_name .= ', ' . $physician_obj->physician_name . ', ';
                        }
                    }
                }
            }
        }

        $data['table'] = View::make('dashboard/partials/admin_hours_table')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('dashboard/rehab_admin_hours')->with($data);
    }

    public function getWeeklyMaxForSelectedPeriod($contract_id, $selected_date)
    {
        $max_hour = DB::table("rehab_max_hours_per_week")
            ->where('contract_id', '=', $contract_id)
            ->where('start_date', '=', mysql_date($selected_date))
            ->first();

        return Response::json($max_hour);
    }

    public function getAdminHours($contract_id, $selected_date)
    {
        $max_hour = DB::table("rehab_admin_hours")
            ->where('contract_id', '=', $contract_id)
            ->where('start_date', '=', mysql_date($selected_date))
            ->first();

        return Response::json($max_hour);
    }

    public function postWeeklyMaxForSelectedPeriod()
    {
        $result = Contract::postWeeklyMaxForSelectedPeriod();

        return Response::json($result);
    }

    public function postAdminHours()
    {
        $result = Contract::postAdminHours();

        return Response::json($result);
    }

    public function updateHospitalActions()
    {
        $data = DutyManagement::updateHospitalActions();
        return $data;
    }

    public function updateHospitalCustomInvoice($hospital_id, $invoice_type)
    {
        $user_id = Auth::user()->id;
        $user = User::findOrFail($user_id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $data = Hospital::updateHospitalCustomInvoice($hospital_id, $invoice_type);
        return $data;
    }

    public function getPendingApprovers($hospital_id)
    {
        if (Request::ajax()) {
            $default_selected_manager = -1;
            $hospitals = Hospital::getApprovalUserHospitals(Auth::user()->id, $default_selected_manager);

            $hospital_ids = [];
            foreach ($hospitals as $key => $value) {
                $hospital_ids [] = $key;
            }

            $approvers = User::select('users.id as id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
                ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id');

            if ($hospital_id == 0) {
                $approvers = $approvers->whereIn('hospital_user.hospital_id', $hospital_ids);
            } else {
                $approvers = $approvers->where('hospital_user.hospital_id', $hospital_id);
            }
            $approvers = $approvers->orderBy('name')
                ->pluck('name', 'id');

            $data = $approvers;
            return $data;
        } else {
            $data = [];
            return $data;
        }
    }
}
