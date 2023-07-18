<?php

namespace App\Http\Controllers;

use App\Console\Commands\PhysicianReportCommand;
use App\Physician;
use App\PhysicianContracts;
use App\PhysicianLog;
use App\Amount_paid;
use App\PhysicianPayment;
use App\User;
use App\Group;
use App\Contract;
use App\ContractType;
use App\Agreement;
use App\PhysicianReport;
use App\Specialty;
use App\InvoiceNote;
use App\PhysicianInterfaceLawsonApcinvoice;
use App\InterfaceType;
use App\Hospital;
use App\PaymentType;
use App\AccessToken;
use App\Signature;
use App\Action;
use App\LogApproval;
use App\PhysicianPracticeHistory;
use App\ApprovalManagerInfo;
use App\Practice;
use App\PhysicianPractices;
use App\ContractDeadlineDays;
use App\HospitalReport;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\ContractRate;
use App\PhysicianHospitalReport;
use App\PaymentStatusDashboard;
use App\customClasses\PaymentFrequencyFactoryClass;
use App\Jobs\UpdatePaymentStatusDashboard;
use App\ActionHospital;
use App\SortingContractName;
use App\HospitalOverrideMandateDetails;
use App\HospitalTimeStampEntry;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\CustomCategoryActions;
use App\AttestationQuestion;
use App\PhysicianType;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\PaymentValidation;
use App\Http\Controllers\Validations\PhysicianInterfaceValidation;
use App\Http\Controllers\Validations\PhysicianValidation;
use stdClass;
use function App\Start\is_physician;
use function App\Start\is_physician_owner;
use function App\Start\is_practice_manager;
use function App\Start\is_practice_owner;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\hospital_report_path;
use function App\Start\physician_report_path;

class PhysiciansController extends ResourceController
{
    protected $requireAuth = true;

    public function getIndex()
    {

        $options = [
            'sort' => Request::input('sort', 5),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'password_text', 'created_at'],
            'per_page' => 9999
        ];

        $data = $this->queryWithUnion('Physician', $options, function ($query, $options) {
            return $query
                ->select('physicians.id', 'physicians.email', 'physicians.last_name', 'physicians.first_name', 'physicians.password_text', 'physicians.created_at', 'physician_practices.practice_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join("practices", "physician_practices.practice_id", "=", "practices.id")
                ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                ->where('hospitals.archived', '=', 0)
                ->whereNull("physician_practices.deleted_at")
                ->distinct();
        });

        $data['table'] = View::make('physicians/_physicians')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/index')->with($data);
    }

    public function getIndexShowAll()
    {
        $options = [
            'sort' => Request::input('sort', 5),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'password_text', 'created_at'],
            'per_page' => 9999
        ];

        $data = $this->query('Physician', $options);
        $data['table'] = View::make('physicians/_physicians')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/index_show_all')->with($data);
    }

    //physician t o all hospital by 1254
    public function getShow($id, $practice_id = 0)
    {
        //physician to multiple hosptial by 1254

        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;

        $physician = Physician::findOrFail($id);

        //drop column practice_id from table 'physicians' changes by 1254 :commented
        //$physician->practice->id=$practice->id;   

        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();

        //physician to multiple hosptial by 1254 : added to view existing phy details
        if (!is_physician_owner($physician->id) && !is_practice_owner($practice_id) && !is_practice_manager())
            App::abort(403);
        //drop column practice_id from table 'physicians' changes by 1254  : commented
        //$practice = Practice::where('id','=',$physician->practice_id)->first();
        if (!$practice) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.practice_doesnot_exist')
            ])->withInput();
        }
        //  $recentLogs = $physician->RecentLogs;
        //   $contracts = $physician->ActiveContracts;

        //    $recentLogs = $physician->recentLogs(10,$practice_id);
        //   $contracts = $physician->activeContracts($practice_id);


        $recentLogs = PhysicianLog::join('physician_contracts', 'physician_contracts.contract_id', '=', 'physician_logs.contract_id')
            ->join("contracts", function ($join) {

                $join->on("physician_logs.physician_id", "=", "physician_contracts.physician_id")
                    ->on("physician_logs.contract_id", "=", "contracts.id");
            })
            ->where("physician_logs.physician_id", "=", $physician->id)
            ->where("contracts.archived", "=", '0')
            ->where("contracts.end_date", "=", "0000-00-00 00:00:00")
            ->where("physician_logs.practice_id", "=", $practice_id)
            ->orderBy('physician_logs.date', 'desc')->limit(10)->get();

        $contracts = $physician->activeContracts($practice_id);
        // added for display activities changed names for on call contracts
        foreach ($recentLogs as $log) {
            //if ($log->contract_type_id == ContractType::ON_CALL){
            if ($log->payment_type_id == PaymentType::PER_DIEM || $log->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $oncallactivity = DB::table('on_call_activities')
                    ->select('name', 'contract_id', 'action_id')
                    ->where("contract_id", "=", $log->contract_id)
                    ->where("action_id", "=", $log->action_id)
                    ->first();
                if ($oncallactivity) {
                    $log->action->name = $oncallactivity->name;
                }
            }

            if ($log->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $log->contract_id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }
        }

        foreach ($contracts as $contract) {
            $contract->uncompenseted_rates = DB::table('contract_rate')
                ->where('contract_rate.contract_id', '=', $contract->contract_id)
                ->where('contract_rate.rate_type', '=', ContractRate::ON_CALL_UNCOMPENSATED_RATE)
                ->where('status', '=', '1')
                ->get();
        }

        if ($practice_id > 0) {
            $practice = Practice::findOrFail($practice_id);
            $data['practice'] = $practice;
            //drop column practice_id from table 'physicians' changes by 1254
            //$physician->practice->id=$practice_id;
            //$physician->practice->name=$practice->name;

            //end-drop column practice_id from table 'physicians' changes by 1254
        }

        foreach ($contracts as $val) {
            $id = $val->id;
            $val->id = $val->contract_id;
            $val->actions = Action::getActions($val);
            $val->id = $id;
        }

        $data['physician'] = $physician;
        $data['recentLogs'] = $recentLogs;
        $data['contracts'] = $contracts;
        // log::info('$contracts', array($contracts));
        $data['user'] = $user;
        $data['table'] = View::make('physicians/_recent_activity')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/show')->with($data);
    }

    //Physician to multiple hosptial by 1254 :added pid
    public function getEdit($id, $practice_id = 0)
    {
//Physicians to multiple hospital by 1254

        $practice = Practice::findOrFail($practice_id);
        $physician = Physician::findOrFail($id);

        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
        $specialties = options(Specialty::orderBy('name', 'ASC')->get(), 'id', 'name');
        $hospital_id = DB::table('practices')->where('id', $practice_id)->value('hospital_id');
        $invoice_notes = InvoiceNote::getInvoiceNotes($id, InvoiceNote::PHYSICIAN, $hospital_id, $practice->id);
        $note_count = count($invoice_notes);
        // $physician_types = options(PhysicianType::all(), 'id', 'type');


        //drop column practice_id from table 'physicians' changes by 1254
        //$physician->practice->id = $practice_id;
        // checking invoice type for LifePoint Helthsystem added by akash
        // $result_hospital = Hospital::findOrFail($hospital_id);
        // $invoice_type = $result_hospital->invoice_type;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        //Physician to multiple hospital by  1254 : changes to pass practice para to compact
        return View::make('physicians/edit')->with(compact('physician', 'specialties', 'hospital_id', 'user', 'note_count', 'invoice_notes', 'practice'));
    }

    /*get all prctices and show for edit*/
    //Physician to multiple hospital by  1254
    //<!-- issue :  Deleted Practice Manager, add existing Practice Manager -->
    public function editPractice($id, $practice_id = 0, $hid)
    {
        $physician = Physician::findOrFail($id);
        //Physician to multiple hospital by  1254
        $practice = Practice::findOrFail($practice_id);

        //drop column practice_id from table 'physicians' changes by 1254 : codereview                               
        //  $physician->practice_id = $practice_id;

        if ($hid == null) {
            $hospital_id = Practice::where('id', $practice_id)->pluck('hospital_id');
        } else {
            $hospital_id = $hid;
        }

        $hospitals = options(Hospital::select('*')->orderBy('name')->get(), 'id', 'name');
        $practices = options(Practice::select('*')->where('hospital_id', $hospital_id)->get(), 'id', 'name');


        //  $practices = options(Practice::select('*')->where('hospital_id', 45)->orderBy('name')->get(), 'id', 'name');

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);
        if (Request::ajax()) {
            return Response::json($practices);
        }
        //Physician to multiple hospital by  1254 : pass practice to compact
        return View::make('physicians/editPractice')->with(compact('physician', 'hospitals', 'practices', 'hospital_id', 'practice'));
    }

    //Physician to multiple hospital by  1254
    public function postEdit($id, $practice_id = 0)
    {
        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);
        //Physician to multiple hospital by  1254
        $practice = Practice::findOrFail($practice_id);
        $result = Physician::editPhysician($id, $practice);

        return $result;
    }

    /*edit practice*/
    //Physician to multiple hospital by  1254
    public function postEditPractice($id, $practice_id = 0)
    {
        $physician = Physician::findOrFail($id);

        //drop column practice_id from table 'physicians' changes by 1254

        //$old_practice_id=$physician->practice_id;
        $old_practice_id = $practice_id;
        $practice = Practice::findOrFail($practice_id);
        // $physician_practices = PhysicianPractices::where('physician_id','=',$id)->where('hospital_id','=',$practice->hospital_id)->pluck('id')->toArray();

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new PhysicianValidation();
        if (!$validation->validateEditPractice(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        if ($old_practice_id != Request::input('practices')) {
            $datetime = date('Y-m-d H:i:s');
            $ischange = PhysicianPracticeHistory::select('*')->where('physician_id', '=', $id)->where('practice_id', '=', $old_practice_id)->orderBy('end_date', 'desc')->first();

            if (strtotime(Request::input('change_date')) < strtotime($datetime)) {
                if (strtotime(Request::input('change_date')) > strtotime($ischange->start_date)) {


                    //if (!$physician->save()) {
                    //     return Redirect::back()->with([
                    //         'error' => Lang::get('physicians.edit_error')
                    //     ]);
                    //}
                    // else{

                    //$ischange = PhysicianPracticeHistory::select('*')->where('physician_id', '=', $id)->where('practice_id', '=', $old_practice_id)->orderBy('end_date', 'desc')->first();
                    if ($ischange != null) {
                        $start_date = mysql_date(Request::input('change_date'));
                        $old_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
                        PhysicianPracticeHistory::where('id', $ischange->id)
                            ->update(array('end_date' => mysql_date($old_end_date)));
                        PhysicianPractices::where('physician_id', '=', $id)->where('practice_id', '=', $old_practice_id)
                            ->update(array('end_date' => mysql_date($old_end_date)));
                        $end_date = mysql_date("2037-12-31 00:00:00");
                    } else {
                        $start_date = Request::input('change_date');
                        $end_date = mysql_date("2037-12-31 00:00:00");
                    }
                    $PhysicianPracticeHistory = new PhysicianPracticeHistory();
                    $PhysicianPracticeHistory->practice_id = Request::input('practices');
                    $PhysicianPracticeHistory->physician_id = $id;
                    $PhysicianPracticeHistory->specialty_id = $physician->specialty_id;
                    $PhysicianPracticeHistory->email = $physician->email;
                    $PhysicianPracticeHistory->first_name = $physician->first_name;
                    $PhysicianPracticeHistory->last_name = $physician->last_name;
                    $PhysicianPracticeHistory->npi = $physician->npi;
                    $PhysicianPracticeHistory->created_at = $datetime;
                    $PhysicianPracticeHistory->start_date = $start_date;
                    $PhysicianPracticeHistory->end_date = $end_date;

                    //drop column practice_id from table 'physicians' changes by 1254 : issue fixed for practice change

                    $new_practice_id = Request::input('practices');
                    $new_practice = Practice::findOrFail($new_practice_id);

                    $physician_practices = new PhysicianPractices();
                    $physician_practices->practice_id = $new_practice_id;
                    $physician_practices->hospital_id = $new_practice->hospital_id;
                    $physician_practices->physician_id = $id;
                    $physician_practices->start_date = $start_date;
                    $physician_practices->end_date = $end_date;
                    $physician_practices->save();


                    if (!$PhysicianPracticeHistory->save()) {
                        return Redirect::back()->with([
                            'error' => Lang::get('physicians.edit_error')
                        ]);
                    } else {
                        $old_hospital_id = DB::table('practices')->where('id', $old_practice_id)->pluck('hospital_id');
                        if ($old_hospital_id[0] != Request::input('hospitals')) {
                            //issue fixed : Hospital-B contracts get deleted after changing practice from hopital-A to Hospital-C by 1254    
                            Contract::where('physician_id', '=', $id)
                                ->where('practice_id', '=', $old_practice_id)
                                ->where('end_date', '=', '0000-00-00 00:00:00')
                                ->update(['end_date' => $datetime]);
                        } elseif ($ischange) {
                            PhysicianLog::where('physician_id', '=', $id)
                                ->where('date', '>', $old_end_date)
                                ->update(['practice_id' => Request::input('practices')]);
                            /* Contract::where('physician_id', '=', $id)
                                ->where('practice_id', '=', $old_practice_id)
                                ->where('end_date', '=', '0000-00-00 00:00:00')
                                ->update(['practice_id' => Request::input('practices')]); */

                            SortingContractName::where('physician_id', '=', $id)
                                ->where('practice_id', '=', $old_practice_id)
                                ->update(['practice_id' => Request::input('practices')]);

                            PhysicianContracts::where('physician_id', '=', $id)
                                ->where('practice_id', '=', $old_practice_id)
                                ->update(['practice_id' => Request::input('practices')]);
                        }
                        return Redirect::back()->with([
                            'success' => Lang::get('physicians.edit_success')
                        ]);
                    }
                    //  }
                } else {
                    return Redirect::back()->with([
                        'error' => Lang::get('physicians.edit_error_before_start_date')
                    ]);
                }
            } else {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.edit_error_after_current_date')
                ]);
            }

        } else {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.edit_error_privious_practice')
            ]);
        }
    }

// physician to multiple hospital by 1254
    public function getDelete($id, $pid = 0)
    {
        $physician = Physician::findOrFail($id);
        $physician_mail = $physician->email;

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $checkPenddingLogs = PhysicianLog::penddingApprovalForPhysician($id);
        if ($checkPenddingLogs) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.approval_pending_error')
            ]);
        }

        $checkPenddingPayments = Amount_paid::penddingPaymentForPhysician($id);
        if ($checkPenddingPayments) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.payment_pending_error')
            ]);
        }

        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $physicianpractices = DB::table("physician_practices")
            ->select("physician_practices.*")
            ->where("physician_practices.physician_id", "=", $id)
            ->where("physician_practices.practice_id", "=", $pid)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            ->orderBy("start_date", "desc")
            //issue : existing phy deleted and again added in same hospital , again deleted gives error fixed
            ->first();


        if (empty($physicianpractices)) {

            return Redirect::back()->with([
                'error' => Lang::get('physicians.practice_enddate_error')
            ]);
        }

        $physicianpractice = PhysicianPractices::findOrFail($physicianpractices->id);

//        $physicianpractice = true;

        if (!$physicianpractice) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.delete_error')
            ]);
        } else {

            $physician = Physician::findOrFail($physicianpractices->physician_id);

//            log::debug('contracts', array($physician->apiContracts()->where('physician_contracts.practice_id','=',$pid)));
            // Below code is for deleting actual physician from physician and user table if it doesnt assigned to any other practice added by akash.
            $physician->apiContracts()->where('physician_contracts.practice_id', '=', $pid)->delete();
            $physician->logs()->where('physician_logs.practice_id', '=', $pid)->delete();
            $physician->reports()->where('physician_reports.practice_id', '=', $pid)->delete();

            $physician_in_other_practices = PhysicianPractices::where("physician_practices.physician_id", "=", $id)
                ->where("physician_practices.practice_id", "!=", $pid)
                // ->whereRaw("physician_practices.start_date <= now()")
                // ->whereRaw("physician_practices.end_date >= now()")
                //issue : existing phy deleted and again added in same hospital , again deleted gives error fixed
                ->get()->count();

            if ($physician_in_other_practices === 0) {
                $physician->delete();
                $user = User::where('email', '=', $physician_mail)
                    ->where('group_id', '=', 6);
                $user->delete();
            }
            // end akash.

        }

        //Physician to Multiple hospital by 1254
        $practice = Practice::findOrFail($pid);
        $data['practice'] = $practice;
        // return Redirect::route('practices.physicians', $physician->practice_id)->with([
        return Redirect::route('practices.physicians', $pid)->with([
            'success' => Lang::get('physicians.delete_success')
        ]);
    }

    public function getResetPassword($id, $pid = 0)
    {
        $physician = Physician::findOrFail($id);
        $practice = Practice::findOrFail($pid);
        $data['practice'] = $practice;
        //physician to multiple hostpial by 1254 : added to view  reset password window for existing phy

        if (!is_physician_owner($physician->id) && !is_practice_owner($pid))
            App::abort(403);
        $randomPassword = randomPassword();
        $physician->setPasswordText($randomPassword);
        $physician->password = Hash::make($randomPassword);
        //reset password for physician in user table
        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
        $user->password_text = $physician->password_text;
        $user->password = Hash::make($randomPassword);
        $user->save();

        if (!$physician->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.reset_error')
            ]);
        }

        $data = [
            'name' => "{$physician->first_name} {$physician->last_name}",
            'email' => $physician->email,
            'password' => $physician->password_text,
            'type' => EmailSetup::RESET_PASSWORD,
            'with' => [
                'name' => "{$physician->first_name} {$physician->last_name}",
                'email' => $physician->email,
                'password' => $physician->password_text
            ]
        ];

        EmailQueueService::sendEmail($data);

        return Redirect::back()->with([
            'success' => Lang::get('physicians.reset_success')
        ]);
    }

    public function getWelcome($id, $pid = 0)
    {
        $physician = Physician::findOrFail($id);
        //drop column practice_id from table 'physicians' changes by 1254
        //$practice = $physician->practice;
        $practice = Practice::findOrFail($pid);
        //end-drop column practice_id from table 'physicians' changes by 1254
        $hospital = $practice->hospital;


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

        return Redirect::back()->with([
            'success' => Lang::get('physicians.welcome_success')
        ]);
    }

    //physician to multiple hospital by 1254: added pid
    public function getContracts(Physician $physician, Practice $practice)
    {
        $id = $physician->id;
        $practice_id = $practice->id;
        $physician = Physician::findOrFail($id);
        //physician to multiple hostpial by 1254 : added to view existing phy contracts  1102
        if (!is_physician_owner($physician->id) && !is_practice_owner($practice_id) && !is_practice_manager())
            App::abort(405);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'filter' => Request::input('filter'),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order', 'filter'],
            //'field_names' => ['agreement_id', 'last_name', 'first_name', 'password_text', 'created']
            //'field_names' => ['agreement_id', 'agreements.start_date', 'agreements.end_date', 'rate', 'created_at','agreements.is_deleted']
            'field_names' => ['sorting_contract_names.sort_order', 'agreement_id', 'agreements.start_date', 'manual_contract_end_date', 'rate', 'created_at', 'agreements.is_deleted'] /*added to get manual contract end date on Nov 4 2019*/

        ];

        $data = $this->query('Contract', $options, function ($query, $options) use ($physician, $practice_id) {
            // Added 6.1.13 START
            $query = $query->select('contracts.*');
            $query->join('sorting_contract_names', 'sorting_contract_names.contract_id', '=', 'contracts.id');
            $query = $query->where(function ($query) use ($physician, $practice_id) {
                $query->where('sorting_contract_names.physician_id', '=', $physician->id)
                    ->where('sorting_contract_names.is_active', '=', 1)
                    ->where('sorting_contract_names.practice_id', '=', $practice_id);
            });
            $query->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id');
            $query = $query->where(function ($query) use ($physician, $practice_id) {
                $query->where('physician_contracts.physician_id', '=', $physician->id)
                    ->whereNull('physician_contracts.deleted_at')
                    ->where('physician_contracts.practice_id', '=', $practice_id);
            });
            // Added 6.1.13 END
            switch ($options['filter']) {
                case 0:
                    $query = $query->whereRaw('contracts.archived = false');
                    $query = $query->whereRaw('contracts.manually_archived = false');/*added for get manually archived contracts on 12DEC2019 */
                    $query = $query->whereRaw('contracts.end_date = "0000-00-00 00:00:00"');
                    break;
                case 1:
                    //$query = $query->whereRaw('contracts.archived = true'); /*remove for get manually archived contracts on 12DEC2019 */
                    $query = $query->where(function ($query) {
                        $query->where("contracts.archived", "=", true)
                            ->orWhere("contracts.manually_archived", "=", true);
                    });/*added for get manually archived contracts on 12DEC2019 */
                    $query = $query->whereRaw('contracts.end_date = "0000-00-00 00:00:00"');
                    break;
                case 2:
                    $query = $query->whereRaw('contracts.end_date = "0000-00-00 00:00:00"');
                    break;
            }

            // $query = $query->whereRaw('contracts.physician_id='.$physician->id);
            // return  $query->where('contracts.practice_id','=',$practice_id);
            return $query;
        });

        //physician to multiple hospital by 1254

        $practice = Practice::findOrFail($practice_id);

        //drop column practice_id from table 'physicians' changes by 1254 :commented
        //$physician->practice->id = $practice_id;
        $data['practice'] = $practice;
        $data['physician'] = $physician;
        $data['table'] = View::make('physicians/_contracts')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians.contracts')->with($data);
    }

    public function getCreateContract(Physician $physician, Practice $practice = null)
    {
        $id = $physician->id;
        $practice_id = $practice->id;
        $physician = Physician::findOrFail($id);
        if (!is_physician_owner($physician->id))
            App::abort(403);

        $data = Physician::getCreateContract($physician, $practice_id);
        if (Request::ajax()) {
            return Response::json($data);
        }
        //Physician to Multiple hospital by 1254
        $practice = Practice::findOrFail($practice_id);
        // $result_hospital = Hospital::findOrFail($practice->hospital_id);

        $hospitals_physicians = DB::table('physicians')
            ->select('physicians.id', DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), 'practices.id as practice_id', 'practices.name as practice_name')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->where('physicians.id', '=', $physician->id)
            ->where('practices.id', '=', $practice->id)
            ->whereNull('physician_practices.deleted_at')
            ->get();

        $data['practice'] = $practice;
        $data['hospitals_physicians'] = $hospitals_physicians;
        $data['hospital'] = Hospital::findOrFail($practice->hospital_id);
        // $data['invoice_type']=$result_hospital->invoice_type;
        return View::make('physicians/create_contract')->with($data);
    }

    public function postCreateContract($id, $practice_id = 0)
    {
        if (!is_physician_owner($id))
            App::abort(403);

        $result = Contract::createContract($id, $practice_id);
        return $result;


    }

    public function getLogs($id, $practice_id)
    {
        $physician = Physician::findOrFail($id);

        //physician to multiple hospital by 1254 : added to view existing phy logs   :1102
        if (!is_physician_owner($physician->id) && !is_practice_owner($practice_id) && !is_practice_manager())
            App::abort(403);

        $options = [
            'sort' => Request::input('sort', 1),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 5,
            'appends' => ['sort', 'order'],
            'field_names' => ['date', 'action_id', 'duration', 'contract_id']
        ];

        $practice = Practice::findOrFail($practice_id);

        $contracts = Contract::select('contracts.*')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->where('physician_contracts.physician_id', '=', $physician->id)
            ->where('physician_contracts.practice_id', '=', $practice_id)
            ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
            ->get();

        $contract_ids = array();///// CHANGES HERE NEW ARRAY YO  STORE contract_type_ids /////////
        $contract_type_ids = array();
        $payment_type_ids = array();
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                $contract_ids[] = $contract->id;
                $contract_type_ids[$contract->id] = $contract->contract_type_id;
                $payment_type_ids[$contract->id] = $contract->payment_type_id;
            }
        } else {
            $contract_ids[] = 0;
        }

        $data = $this->query('PhysicianLog', $options, function ($query, $options) use ($physician, $contract_ids) {
            return $query->where('physician_id', '=', $physician->id)->whereIn('contract_id', $contract_ids);
        });

        //drop column practice_id from table 'physicians' changes by 1254
        // $physician->practice->id=$practice_id;
        // $physician->practice->name=$practice->name;
        //drop column practice_id from table 'physicians' changes by 1254

        $data['physician'] = $physician;
        // CHANGES HERE FOR CHANGING ACTION NAMES ON LOG TAB OF PHYSICIAN
        foreach ($data['items'] as $log) {

            //if ($contract_type_ids[$log->contract_id] == ContractType::ON_CALL){
            if ($payment_type_ids[$log->contract_id] == PaymentType::PER_DIEM || $payment_type_ids[$log->contract_id] == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $oncallactivity = DB::table('on_call_activities')
                    ->select('name', 'contract_id', 'action_id')
                    ->where("contract_id", "=", $log->contract_id)
                    ->where("action_id", "=", $log->action_id)
                    ->first();
                if ($oncallactivity) {
                    $log->action->name = $oncallactivity->name;
                }
            }
            try {
                $contract_name = DB::table('contracts')
                    ->select('contract_names.name as contract_name', 'agreements.approval_process', 'contracts.partial_hours', 'contracts.payment_type_id')   //contract partial off to on changes
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->where("contracts.id", "=", $log->contract_id)
                    ->first();
            } catch (Exception $e) {
                $contract_name = 'Unknown';
            }
            if ($contract_name) {
                $log->contract_name = $contract_name->contract_name;
                $log->approval_process = $contract_name->approval_process;
                //contract partial off to on changes 
                $log->partial_hours = $contract_name->partial_hours;
                $log->payment_type_id = $contract_name->payment_type_id;
            }

            if ($log->approval_process != 1) {
                $log->physician_approved = ($log->signature != 0 || $log->approval_date != '0000-00-00') ? "Y" : "N";
            } else {
                $log_approvals = LogApproval::where('log_id', '=', $log->id)
                    ->get();
                if ($log_approvals->count() > 0) {
                    $log->physician_approved = "Y";
                } else {
                    $log->physician_approved = "N";
                }
            }

            if ($payment_type_ids[$log->contract_id] == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $log->contract_id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }
        }
        $data['practice'] = $practice;
        $data['table'] = View::make('physicians/_logs')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }
//physicians to multiple hospital by 1254

        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;
        return View::make('physicians/logs')->with($data);

    }

//physicians to multiple hospital by 1254 : added pid
    public function getDeleteLog($physician_id, $log_id, $pid = 0)

    {
        $physician = Physician::findOrFail($physician_id);
        $log = PhysicianLog::findOrFail($log_id);
        $temp_log_detail = $log;


        if (!is_physician_owner($physician->id) && !is_practice_owner($pid) && !is_practice_manager())
            App::abort(403);

        if ($log->next_approver_level == 0 && $log->next_approver_user == 0 && $log->approval_date == "0000-00-00") {
            if (!$log->delete()) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.delete_log_error')
                ]);
            } else {
                $contract = Contract::findOrFail($log->contract_id);
                $agreement = Agreement::findOrFail($contract->agreement_id);
                UpdatePaymentStatusDashboard::dispatch($temp_log_detail->physician_id, $temp_log_detail->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $log->date);

                return Redirect::back()->with([
                    'success' => Lang::get('physicians.delete_log_success')
                ]);
            }
        } else {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.delete_on_call_approved_log')
            ]);
        }

    }

    public function getReports($id, $practice_id = 0)
    {
        $physician = Physician::findOrFail($id);

        if (!is_physician_owner($physician->id) && !is_practice_owner($practice_id))
            App::abort(403);

        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        //one-many physician issue fixed for showing old physician report for existing hospital by 1254
        //added new query for fixes

        // $data = $this->query('PhysicianReport', $options, function ($query, $options) use ($physician) {
        //     return $query->where('physician_id', '=', $physician->id);
        // });

        $data = $this->query('PhysicianReport', $options, function ($query, $options) use ($physician, $practice_id) {
            $query->select('physician_reports.*')
                ->where('physician_reports.physician_id', '=', $physician->id)
                ->where('physician_reports.practice_id', '=', $practice_id);
            return $query;
            //end-one-many physician issue fixed for showing old physician report for existing hospital by 1254
        });

        $contract_types = ContractType::getPhysicianOptions($physician->id, $practice_id);
        $default_contract_key = key($contract_types);
        $practice = Practice::findOrFail($practice_id);

        //drop column practice_id from table 'physicians' changes by 1254
        // $physician->practice->id=$practice_id;
        // $physician->practice->name=$practice->name;
        //drop column practice_id from table 'physicians' changes by 1254

        $data['practice'] = $practice;
        $data['physician'] = $physician;
        $data['table'] = View::make('physicians/_reports')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data["contract_type"] = Request::input("contract_type", $default_contract_key);
        $data['form_title'] = "Generate Report";
        $data["contract_types"] = $contract_types;
        // $data['agreements'] = Agreement::getPhysicianAgreementData($physician->id, $data["contract_type"]);
        //Physician to Multiple hospital by 1254
        //drop column practice_id from table 'physicians' changes by 1254 : passed practice_id
        $data['agreements'] = Agreement::getPhysicianAgreementData($physician, $data["contract_type"], $practice_id);
        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;
        $data['form'] = View::make('layouts/_reports_form')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/reports')->with($data);
    }
    //one-many physician issue fixed for showing old physician report for existing hospital by 1254 
    //added $practice_id to function
    public function postReports($id, $practice_id)
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
        // log::info("physician  localtimeZone",array($localtimeZone));

        $physician = Physician::findOrFail($id);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $agreement_ids = Request::input('agreements');
        $months = [];

        // if (count($agreement_ids) == 0) { // Old condition
        if ($agreement_ids == null) {  // New condition added by akash.
            return Redirect::back()->with([
                'error' => Lang::get('physicians.report_selection_error')
            ]);
        }

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
        }

        $agreement_ids = implode(',', $agreement_ids);
        $months = implode(',', $months);

        Artisan::call('reports:physician', [
            'physician' => $physician->id,
            'contract_type' => Request::input('contract_type'),
            'agreements' => $agreement_ids,
            'months' => $months,
            //one-many physician issue fixed for showing old physician report for existing hospital by 1254
            'practice_id' => $practice_id,
            'localtimeZone' => $localtimeZone
            //end-one-many physician issue fixed for showing old physician report for existing hospital by 1254
        ]);

        if (!PhysicianReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PhysicianReportCommand::$message)
            ]);
        }

        $report_id = PhysicianReportCommand::$report_id;
        $report_filename = PhysicianReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(PhysicianReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    public function getReport($physician_id, $report_id)
    {
        $physician = Physician::findOrFail($physician_id);
        $report = $physician->reports()->findOrFail($report_id);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $filename = physician_report_path($physician, $report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getDeleteReport($physician_id, $report_id)
    {
        $physician = Physician::findOrFail($physician_id);
        $report = PhysicianReport::findOrFail($report_id);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        if (!$report->delete()) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.delete_report_error')
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get('physicians.delete_report_success')
        ]);
    }

    public function getPayments($physicianId)
    {
        $physician = Physician::findOrFail($physicianId);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $options = [
            "sort" => Request::input('sort'),
            "order" => Request::input('order', 2),
            "sort_min" => 1,
            "sort_max" => 3,
            "appends" => ['sort', 'order'],
            "field_names" => ['agreement_id', "month", "amount"]
        ];

        $data = $this->query('PhysicianPayment', $options, function ($query, $options) use ($physician) {
            return $query->where('physician_payments.physician_id', '=', $physician->id);
        });

        $data["physician"] = $physician;
        $data["agreements"] = Agreement::listByPhysician($physician);

        /*added conditions to check agreements are available or not on 14/04/2016*/
        if (Request::has("agreement")) {
            $data["agreement"] = Request::input("agreement");
        } else {
            if ($data["agreements"] != 0) {
                $data["agreement"] = key($data["agreements"]);
            } else {
                $data["agreement"] = '';
            }
        }

        //


        if ($data["agreements"] != '') {
            // Fetch information related to the current agreement.
            $agreement = Agreement::getAgreementData($data["agreement"]);

            // Append month information to the data array.
            $data["months"] = $agreement->dates;
            $data["month"] = $agreement->current_month;
            $current = $agreement->current_month;
            if (Request::has("month")) {
                $data["month"] = Request::input("month");
                $current = Request::input("month");
            }
            $remaining = 0.0;
            if (count($data["months"]) > 0) {
                if ($current < 1) {
                    $current = count($data["months"]);
                }
                $current_month = explode(": ", $data["months"][$current]);
                $current_month_date = explode("-", $current_month[1]);
                $start_date = mysql_date($current_month_date[0]);
                $end_date = mysql_date($current_month_date[1]);
                $contracts = DB::table('contracts')->select('*')->where('agreement_id', $data["agreement"])->where('physician_id', $physicianId)->get();
                foreach ($contracts as $contract) {

                    $logs = PhysicianLog::select(
                        DB::raw("actions.name as action"),
                        DB::raw("actions.action_type_id as action_type_id"),
                        DB::raw("physician_logs.date as date"),
                        DB::raw("physician_logs.duration as worked_hours"),
                        DB::raw("physician_logs.signature as signature"),
                        DB::raw("physician_logs.approval_date as approval_date"),
                        DB::raw("physician_logs.details as notes")
                    )
                        ->join("actions", "actions.id", "=", "physician_logs.action_id")
                        ->where("physician_logs.contract_id", "=", $contract->id)
                        ->where("physician_logs.physician_id", "=", $physicianId)
                        ->whereBetween("physician_logs.date", [$start_date, $end_date])
                        ->orderBy("physician_logs.date", "asc")
                        ->get();

                    $calculated_payment = 0;
                    foreach ($logs as $log) {
                        if (($log->approval_date != '0000-00-00') || ($log->signature != 0)) {
                            $logduration = $log->worked_hours;
                            $contract_data = DB::table('contracts')
                                ->where('id', '=', $contract->id)
                                ->get();
                            $rate = 0;

                            //if($contract->contract_type_id != ContractType::ON_CALL)
                            if ($contract->payment_type_id != PaymentType::PER_DIEM) {
                                //Log::info("Contract data",array($contract_data));
                                $rate = $contract->rate;
                            } else {

                                if (strlen(strstr(strtoupper($log->action), "WEEKDAY")) > 0) {
                                    $rate = $contract->weekday_rate;
                                } else if (strlen(strstr(strtoupper($log->action), "WEEKEND")) > 0) {
                                    $rate = $contract->weekend_rate;
                                } else if (strlen(strstr(strtoupper($log->action), "HOLIDAY")) > 0) {
                                    $rate = $contract->holiday_rate;
                                }
                            }

                            $logpayment = $logduration * $rate;
                            $calculated_payment = $calculated_payment + $logpayment;
                        }

                    }

                    $amount_paid_hospital = DB::table('amount_paid')
                        ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"))
                        ->where('physician_id', '=', $physicianId)
                        ->where("start_date", '=', $start_date)
                        ->where("end_date", '=', $end_date)
                        ->first();
                    if ($amount_paid_hospital->amount_paid_hospital == null) {
                        $amount_paid_hospital->amount_paid_hospital = 0;
                    }


                    $amount_paid_physician = DB::table('physician_payments')
                        ->select(DB::raw("sum(physician_payments.amount) as amount_paid_physician"))
                        ->where('physician_id', '=', $physicianId)
                        ->where('agreement_id', '=', $data["agreement"])
                        ->first();
                    if ($amount_paid_physician->amount_paid_physician == null) {
                        $amount_paid_physician->amount_paid_physician = 0;
                    }

                    $amount_paid = $amount_paid_hospital->amount_paid_hospital + $amount_paid_physician->amount_paid_physician;
                    $remaining += $calculated_payment - $amount_paid;
                }
            }
            if ($remaining < 0) {
                $remaining = 0;
            }
            $data["remaining"] = $remaining;
        } else {
            $data["months"] = '';
            $data["month"] = '';
            $data["remaining"] = 0;
        }


        $data["form"] = View::make("physicians/payments/_form")->with($data)->render();
        $data["table"] = View::make("physicians/payments/_table")->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make("physicians/payments/index")->with($data);
    }

    public function postPayments($physicianId)
    {
        $physician = Physician::findOrFail($physicianId);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $validation = new PaymentValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $payment = new PhysicianPayment;
        $payment->agreement_id = Request::input("agreement");
        $payment->physician_id = $physician->id;
        $payment->month = Request::input("month");
        $payment->amount = Request::input("amount");
        $payment->save();

        return Redirect::back()->with([
            "success" => Lang::get("physicians.create_payment_success")
        ]);
    }

    public function getDeletePayment($physicianId, $paymentId)
    {
        $physician = Physician::findOrFail($physicianId);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $payment = $physician->payments()->findOrFail($paymentId);
        $payment->delete();

        return Redirect::back()->with([
            "success" => Lang::get("physicians.delete_payment_success")
        ]);
    }

    /*private function getAgreements($hospital_id)
    {
        /* where clause of is_deleted added for soft delete
            Code modified_on: 07/04/2016
            *//*
        return Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where ('is_deleted','=',false)
            ->pluck('name', 'id');
    }*//*move to model */

    /*private function contractTypes()
    {
        return options(ContractType::all(), 'id', 'name');
    }*//*move to model*/

    public function physicianDashboard($physician_id)
    {


        if (!is_physician())
            App::abort(403);

        $physician = Physician::findOrFail($physician_id);
        //Physician to Multiple hospital by #1254
        $data["hospital_names"] = PhysicianPractices::getHospitals($physician_id);
        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
        $today = date("Y-m-d");
        if ($user) {
            if ($user->password_expiration_date <= $today) {
                return Redirect::route('physician.expired', $physician->id);
            }
        }
        $physician_logs = new PhysicianLog();
        $rejected = false;
        $contracts = array();

        $hospitals = DB::table("physician_practices")
            ->select("physician_practices.hospital_id")
            ->where("physician_practices.physician_id", "=", $physician_id)
            ->whereNull("physician_practices.deleted_at")
            ->orderBy("start_date", "desc")
            ->get();

        $hospital_count = count($hospitals);
        $data['hospital_count'] = $hospital_count;

        $override_mandate_details = array();
        $time_stamp_entries = array();
        foreach ($data["hospital_names"] as $hospital) {
            $override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $hospital["hospitalid"])
                ->where('is_active', '=', 1)
                ->get();

            $time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
                ->where("hospital_id", "=", $hospital["hospitalid"])
                ->where('is_active', '=', 1)
                ->get();

            //drop column practice_id from table 'physicians' changes by 1254 : codereview
            $physicianpractices = DB::table("physician_practices")
                ->select("physician_practices.practice_id")
                ->where("physician_practices.physician_id", "=", $physician_id)
                ->where("physician_practices.hospital_id", "=", $hospital["hospitalid"])
                ->whereRaw("physician_practices.start_date <= now()")
                ->whereRaw("physician_practices.end_date >= now()")
                ->whereNull("physician_practices.deleted_at")
                ->orderBy("start_date", "desc")
                ->first();

            $physician->practice_id = $physicianpractices->practice_id;


            $active_contracts = $physician->contracts()
                ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.payment_frequency_type")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                // ->whereRaw("practices.id = $physician->practice_id")
                ->whereRaw("practices.id = $physicianpractices->practice_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                //            ->join('physician_practices','physician_practices.practice_id','=',"contracts.practice_id")
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
                        $rejected_logs = $physician_logs->rejectedLogs($contract->id, $physician_id);
                        if (count($rejected_logs) > 0) {
                            $rejected = true;
                        }
                    }
                    $contracts[] = [
                        "id" => $contract->id,
                        "contract_type_id" => $contract->contract_type_id,
                        "payment_type_id" => $contract->payment_type_id,
                        "name" => contract_name($contract),
                        "start_date" => format_date($contract->agreement->start_date),
                        "end_date" => format_date($contract->manual_contract_end_date),
                        //                    "rate"       => formatCurrency($contract->rate),
                        "rate" => $contract->rate,
                        "weekday_rate" => $contract->weekday_rate,
                        "weekend_rate" => $contract->weekend_rate,
                        "holiday_rate" => $contract->holiday_rate,
                        "mandate_details" => $contract->mandate_details,
                        "holiday_on_off" => $contract->holiday_on_off,  // physicians log the hours for holiday activity on any day
                        "partial_hours" => $contract->partial_hours,
                        "partial_hours_calculation" => $contract->partial_hours_calculation,
                        "payment_frequency_type" => $contract->payment_frequency_type
                    ];
                }
            }
        }


        $data['physician'] = $physician;
        $data['contracts'] = $contracts;
        $data['rejected'] = $rejected;
        $data['hospitals_override_mandate_details'] = $override_mandate_details;
        $data['time_stamp_entries'] = $time_stamp_entries;

        return View::make("physicians.physician_dashboard")->with($data);
    }

    public function getContractsForHospitals($hospital_id, $physicianid)
    {


        $contract_name = PhysicianPractices::getContractsForHospital($hospital_id, $physicianid);


        return Response::json([
            "contractname" => $contract_name
        ]);


    }

    public function getSignature($physician_id, $practice_id = 0)
    {
        $date_check = date('Y-m') . "-01";
        $accesstoken = AccessToken::where("physician_id", "=", $physician_id)->orderBy("expiration", "DESC")->first();
        /*$signature = DB::table('signature')
            ->select("signature.*")
            ->where("physician_id", "=", $physician_id)
            ->where("date", ">=", $date_check)
            ->orderBy("created_at","desc")
            ->first();*/
        $signature = DB::table('signature')->where("physician_id", "=", $physician_id)
            ->orderBy("created_at", "desc")->first();


        // $data['physician']=Physician::findOrFail($physician_id);
        $physician = Physician::findOrFail($physician_id);
        $physician->practice_id = $practice_id;
        // <!-- One to many :issue-5 submit signature by 1254 : 31052021-->

        $practice = Practice::findOrFail($practice_id);

        $data['practice'] = $practice;
        $data['physician'] = $physician;

        if (!$signature) {
            $date = mysql_date(date('Y-m-d'));
            $data['date_selector'] = $date;
            return View::make("physicians.signature_edit")->with($data);
        } else {
            $data['signature'] = $signature->signature_path;
            return View::make("physicians.signature")->with($data);
        }
    }

    // <!-- One to many :issue-5 submit signature by 1254 : 16022021-->
    // <!-- added practice id of physician to route  -->

    public function getSignature_edit($physician_id, $practice_id = 0)
    {
        $date_check = date('Y-m') . "-01";
        $accesstoken = AccessToken::where("physician_id", "=", $physician_id)->orderBy("expiration", "DESC")->first();
        /*$signature = DB::table('signature')
            ->select("signature.*")
            ->where("physician_id", "=", $physician_id)
            ->where("date", ">=", $date_check)
            ->orderBy("created_at","desc")
            ->first();*/
        $signature = DB::table('signature')->where("physician_id", "=", $physician_id)
            ->orderBy("created_at", "desc")->first();
        $date = mysql_date(date('Y-m-d'));
        $data['signature'] = $signature->signature_path;

        //drop pracitce_id issue fixed : User “Submit signature” not saves signature.
        //$data['physician']=Physician::findOrFail($physician_id);
        $physician = Physician::findOrFail($physician_id);
        $physician->practice_id = $practice_id;
        $data['physician'] = $physician;

        //drop column practice_id from table 'physicians' changes by 1254 : added practice_id in data
        $data['practice_id'] = $practice_id;
        $data['date_selector'] = $date;
        return View::make("physicians.signature_edit")->with($data);
    }
// <!-- One to many :issue-5 submit signature by 1254 : 16022021-->
    // <!-- added practice id of physician to route  -->

    public function submitLogForOnCall()
    {
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
        $user_id = $physician_id;

        $contract_id = Request::input("contractId");
        // added on 2018-12-20
        //call-coverage-duration  by 1254 : get duration from slider
        $contract = Contract::findOrFail($contract_id);
        if ($contract->partial_hours == 1 && ($contract->payment_type_id == 3 || $contract->payment_type_id == 5)) {
            $duration = Request::input("duration");
        }
        if ((Request::input("action") != "") || (Request::input("action") != null)) {
            // $agreement_id=
            $selected_dates = Request::input("dates");
            $physician_log = new PhysicianLog();
            //call-coverage-duration  by 1254 : passed duration
            return $physician_log->submitMultipleLogForOnCall($action_id, $shift, $log_details, $physician_id, $contract_id, $selected_dates, $duration, $start_time, $end_time);
        } else {
            return "both_actions_empty_error";
        }
    }

    public function postSaveLog()
    {
        $action_id = Request::input("action");
        $shift = Request::input("shift");
        $duration = Request::input("duration");
        $log_details = Request::input("notes");
        $physician_id = Request::input("physicianId");
        $physician = Physician::findOrFail($physician_id);
        $user_id = $physician_id;
        $start_time = Request::input("start_time", "");
        $end_time = Request::input("end_time", "");

        $hospital_id = Request::input("hospitalId");
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

        /* TO DO */
        // if (Request::input("action") === "" || Request::input("action") == null || Request::input("action") == -1) {
        if (Request::input("action") === "" || Request::input("action") == null) {
            return "both_actions_empty_error";
        }
        if (Request::input("action") == -1) {
            $action = new Action;
            $action->name = Request::input("action_name");
            $action->contract_type_id = Request::input("contract_type_id");
            $action->payment_type_id = Request::input("payment_type_id");
            $action->action_type_id = 5;
            $action->save();
            $action_id = $action->id;
        }
        $contract_id = Request::input("contractId");
        $contract = Contract::findOrFail($contract_id);
        $getAgreementID = DB::table('contracts')
            ->where('id', '=', $contract_id)
            ->pluck('agreement_id');
        $agreement = Agreement::findOrFail($getAgreementID);
        $start_date = $agreement->first()->start_date;
        //$end_date=$agreement->end_date;
        $end_date = $contract->manual_contract_end_date;
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
                    if ($checkHours === 'Under 24') {
                        return $physician_log->saveLogs($action_id, $shift, $log_details, $physician, $contract_id, $selected_date, $duration, $user_id, $start_time, $end_time);
                    } else {
                        return $checkHours;
                    }
                } else {
                    return "Excess 365";
                }
            }
        }
    }

    public function checkDuretion()
    {
        //check duration for day 24
        // check duration for annual cap 23 MAR 2018
        $contractId = Request::input("contractId");
        $physicianId = Request::input("physicianId");
        $selected_dates = Request::input("dates");
        $duration = Request::input("duration");
        $log = new PhysicianLog();
        return $log->getHoursCheck($contractId, $physicianId, $selected_dates[0], $duration);
    }

    public function getApproveSignature($physician_id, $contract_id, $date_selector = "All")
    {
        $date_check = date('Y-m') . "-01";
        $token = "dashboard";
        $signature = DB::table('signature')
            ->select("signature.*")
            ->where("physician_id", "=", $physician_id)
            //->where("date", ">=", $date_check)
            ->orderBy("created_at", "desc")
            ->first();
        $contract = Contract::findOrFail($contract_id);
        $physician_obj = Physician::findOrFail($physician_id)->contracts()->where('contracts.id', '=', $contract->id)->first();
        $practice_id = $physician_obj->pivot->practice_id;
//        log::debug('$physician_obj', array($physician_obj->pivot->practice_id));
        $data['practice_id'] = $practice_id;

        //drop column practice_id from table 'physicians' changes by 1254
        $physician = Physician::findOrFail($physician_id);
        $physician->practice_id = $practice_id;
        $data['physician'] = $physician;
        //end drop column practice_id from table 'physicians' changes by 1254

        $data['contract_id'] = $contract_id;
        $data['date_selector'] = $date_selector;
        $data['user_type'] = Auth::user()->group_id;
//        $contract = Contract::findOrFail($contract_id);
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $data['hospital_id'] = PhysicianPractices::where('physician_id', '=', $physician_id)
            ->where('practice_id', '=', $practice_id)
            ->whereRaw("start_date <= now()")
            ->whereRaw("end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->first()->hospital_id;

        if (!$signature && $signature == null) {
            if (is_physician()) {
                return View::make("physicians.signatureApprove_edit")->with($data);
            } else {
                return Redirect::back()->with(['error' => Lang::get('physician_interface.no_signature_physician')]);
            }
        } else {
            $data['signature_id'] = $signature->signature_id;
            $data['signature'] = $signature->signature_path;
            $data['payment_type_id'] = $contract->payment_type_id;
            return View::make("physicians.signatureApprove")->with($data);
        }
    }

    public function getApproveSignature_edit($physician_id, $contract_id, $date_selector = "All")
    {
        $date_check = date('Y-m') . "-01";
        $data['physician'] = Physician::findOrFail($physician_id);
        $data['contract_id'] = $contract_id;
        $contract = Contract::findOrFail($contract_id);
        $data['practice_id'] = $contract->practice_id;
        $data['date_selector'] = $date_selector;
        return View::make("physicians.signatureApprove_edit")->with($data);
    }

    public function postApproveSignature()
    {
        set_time_limit(0);
        $physician_id = Request::input("physician_id");
        $date = mysql_date(date('Y-m-d'));
        $signArray = explode(',', Request::input("signature"), 2);
        $date_selector = "All";
        $date_selector = Request::input("date_selector");
        $signature = $signArray[1];
        $accesstoken = AccessToken::where("physician_id", "=", $physician_id)
            ->orderBy("created_at", "desc")->first();
        if (!$accesstoken) {
            $token = "dashboard";
        } else {
            $token = $accesstoken->key;
        }
        $signature_obj = new Signature();
        $result = $signature_obj->postSignature($physician_id, $signature, $token, $date, "approve");
        $signature_id = $result;
        //return $this->approveLogs($physician_id,Request::input("contract_id"),$signature_id,"new");
        $physician_log = new PhysicianLog();
        $results = $physician_log->approveLogsDashboard($physician_id, Request::input("contract_id"), $signature_id, "new", $date_selector);

        if ($results == 1) {
            $questions_answer_annually = json_decode(Request::input('questions_answer_annually'));
            $questions_answer_monthly = json_decode(Request::input('questions_answer_monthly'));
            $dates_range = json_decode(Request::input('date_range'));

            if ($questions_answer_annually || $questions_answer_monthly) {
                AttestationQuestion::postAttestationQuestionsAnswer($physician_id, Request::input("contract_id"), $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range);
            }
        }

        return $results;
    }

    public function postSignature()
    {
        $physician_id = Request::input("physician_id");
        $signArray = explode(',', Request::input("signature"), 2);
        $signature = $signArray[1];
        $accesstoken = AccessToken::where("physician_id", "=", $physician_id)
            ->orderBy("created_at", "desc")->first();
        if (!$accesstoken) {
            $token = "dashboard";
        } else {
            $token = $accesstoken->key;
        }
        $date = mysql_date(date('Y-m-d'));
        $signature_obj = new Signature();
        $result = $signature_obj->postSignature($physician_id, $signature, $token, $date);
        return $result;
    }

    public function approveLogs($physician_id, $contract_id, $signature_id, $type = "old")
    {
        $signature = Signature::where("signature_id", "=", $signature_id)->first();
        $physician_log = new PhysicianLog();
        $result = $physician_log->approveLogsDashboard($physician_id, $contract_id, $signature_id, $type);
        $user_type = Request::input("user_type");


        $data['physician'] = Physician::findOrFail($physician_id);
        $data['contract_id'] = $contract_id;
        $contract = Contract::findOrFail($contract_id);

        $practice_obj = PhysicianContracts::where('contract_id', '=', $contract_id)->where('physician_id', '=', $physician_id)->whereNull('deleted_at')->first();
        $data['practice_id'] = $practice_obj->practice_id;
        $data['signature_id'] = $signature->signature_id;
        $data['signature'] = $signature->signature_path;
        $data['user_type'] = $user_type;
        if ($result == 1) {
            $data['msg'] = "Logs approved successfully.";
        } else {
            $data['msg'] = "Logs not approved.";
        }
        return View::make("physicians.signatureApprove")->with($data);
    }

    public function approveAllLogs()
    {
        set_time_limit(0);
        $physician_id = Request::input("p_id");
        $contract_id = Request::input("c_id");
        $signature_id = Request::input("s_id");
        $date_selector = Request::input("date_selector");
        $type = Request::input("type");
        $user_type = Request::input("user_type");
        $signature = Signature::where("signature_id", "=", $signature_id)->first();
        $contract = Contract::findOrFail($contract_id);
        $physician_log = new PhysicianLog();
        $result = $physician_log->approveLogsDashboard($physician_id, $contract_id, $signature_id, $type, $date_selector);


        $physician_contract_obj = PhysicianContracts::where('physician_id', '=', $physician_id)
            ->where('contract_id', '=', $contract_id)
            ->whereNull('deleted_at')
            ->first();

        if ($physician_contract_obj) {
            $contract_practice_id = $physician_contract_obj->practice_id;
        }
        //drop column practice_id from table 'physicians' changes by 1254  ::
        //$data['physician']=Physician::findOrFail($physician_id);
        $physician = Physician::findOrFail($physician_id);
        $physician->practice_id = $contract_practice_id;
        $data['physician'] = $physician;
        //end drop column practice_id from table 'physicians' changes by 1254


        $data['contract_id'] = $contract_id;
        $data['signature_id'] = $signature->signature_id;
        $data['signature'] = $signature->signature_path;
        $data['user_type'] = $user_type;
        $data['date_selector'] = "All";
        $contract = Contract::findOrFail($contract_id);
        $data['practice_id'] = $contract_practice_id;
        $data['payment_type_id'] = $contract->payment_type_id;

        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $data['hospital_id'] = PhysicianPractices::where('physician_id', '=', $physician_id)
            ->where('practice_id', '=', $contract_practice_id)
            ->whereRaw("start_date <= now()")
            ->whereRaw("end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->first()->hospital_id;

        if ($result != -1) {
            $data['msg'] = "Logs approved successfully.";

            $questions_answer_annually = json_decode(Request::input('questions_answer_annually'));
            $questions_answer_monthly = json_decode(Request::input('questions_answer_monthly'));
            $dates_range = json_decode(Request::input('date_range'));

            if ($questions_answer_annually || $questions_answer_monthly) {
                AttestationQuestion::postAttestationQuestionsAnswer($physician_id, $contract_id, $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range);
            }
        } else {
            $data['msg'] = "Logs not approved.";
        }
        if ($contract->payment_type_id == PaymentType::PSA or $user_type == 3) {

            //issue fixed : redirect to wrong practice after submitting aprroved log by practice manager by 1254
            return Redirect::route('practices.contracts', $contract_practice_id)->with(['success' => "Logs approved successfully."]);
            //end-issue fixed : redirect to wrong practice after submitting aprroved log by practice manager by 1254

            //return Redirect::route('practices.contracts', Physician::findOrFail($physician_id)->practice_id)->with(['success' => "Logs approved successfully."]);
        } else {
            return View::make("physicians.signatureApprove")->with($data);
        }
    }

    public function getChangePassword($id)
    {
        $physician = Physician::findOrFail($id);

        if (!is_physician()) {
            App::abort(403);
        }

        return View::make('physicians/change_password')->with([
            'physician' => $physician,
            'groups' => Group::pluck('name', 'id')
        ]);
    }

    public function getExpired($id)
    {
        $physician = Physician::findOrFail($id);
        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();

        if (!is_physician()) {
            App::abort(403);
        }

        return View::make('physicians/expired')->with([
            'physician' => $physician,
            'user' => $user,
            'groups' => Group::pluck('name', 'id')
        ]);
    }

    public function getRejected($physician_id, $contact_id, $hospital_id)
    {

        $physician = Physician::findOrFail($physician_id);
        //Physician to Multiple hospital by #1254
        $data["hospital_names"] = PhysicianPractices::getHospitals($physician_id);
        $data["hospitals"] = PhysicianPractices::fetchHospitals($physician_id);

        $default_hospital_key = key($data["hospitals"]);
        $hospital_id = $hospital_id != 0 ? $hospital_id : $default_hospital_key;

        $hospital_with_contracts = [];
        $active_contracts_list = [];

        $hospital_ids = PhysicianPractices::select("hospitals.name as hospital_name", "physician_practices.hospital_id", "physician_practices.practice_id")
            ->join("hospitals", "hospitals.id", "=", "physician_practices.hospital_id")
            ->where("physician_practices.physician_id", "=", $physician_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            ->distinct()
            ->pluck("hospital_name", "hospital_id", "physician_practices.practice_id");

        foreach ($hospital_ids as $hosp_id => $hospital_name) {
            $active_contracts = $physician->contracts()
                // ->select("contracts.*","contract_names.name as contract_name")
                ->select("contracts.*", DB::raw("concat(concat(contract_names.name, ' ( ', practices.name), ' ) ') as contract_name"))
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                //  ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id") // Sprint 6.1.13
                ->join("physician_practices", "physician_practices.practice_id", "=", "sorting_contract_names.practice_id")
                ->join("practices", "practices.id", "=", "sorting_contract_names.practice_id")
                ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                //->whereRaw("practices.id = $physician->practice_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                ->where("practices.hospital_id", "=", $hospital_id)
                ->where(function ($query) {
                    $query->whereRaw("agreements.end_date >= now()")
                        ->orwhereRaw("agreements.valid_upto >= now()")
                        ->distinct();
                })
                ->orderBy("sorting_contract_names.practice_id", "ASC")
                ->orderBy("sorting_contract_names.sort_order", "ASC")   // Sprint 6.1.13
                ->pluck("contract_name", "id");

            if (count($active_contracts) > 0) {
                $hospital_with_contracts[$hosp_id] = $hospital_name;
                if ($hospital_id == 0) {
                    $hospital_id = $hosp_id;
                    if ($contact_id == 0) {
                        $contact_id = key($active_contracts);
                    }
                    $active_contracts_list = $active_contracts;
                } else if ($hospital_id == $hosp_id) {
                    $hospital_id = $hosp_id;
                    if ($contact_id == 0) {
                        $contact_id = key($active_contracts);
                    }
                    $active_contracts_list = $active_contracts;
                }
            }
        }

        $data["hospitals"] = $hospital_with_contracts;

        $hospital_override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        $default_contract_key = key($active_contracts);

        //$data['physician']=$physician;

        //issue fixed : redirection from rejected button to submit signature showing 404 error
        $practice_id = PhysicianPractices::where("physician_id", "=", $physician_id)
            ->where("hospital_id", "=", $hospital_id)
            ->pluck('practice_id')->toArray();

        $physician->practice_id = $practice_id[0];

        $data['physician'] = $physician;
        $data['contracts'] = $active_contracts_list;
        $data['hospital'] = $hospital_id != 0 ? $hospital_id : $default_hospital_key;
        $data['contract'] = $contact_id != 0 ? $contact_id : $default_contract_key;
        $rejected_logs = array();
        $physician_logs = new PhysicianLog();

        if ($contact_id == 0) {
            foreach ($active_contracts_list as $id => $name) {

                $rejected_logs = $physician_logs->rejectedLogs($id, $physician_id);
                if (count($rejected_logs) > 0) {
                    $data['contract'] = $id;
                    break;
                } else {
                    continue;
                }
            }
        } else {

            $rejected_logs = $physician_logs->rejectedLogs($data['contract'], $physician_id);
        }

        $data['logs'] = $rejected_logs;
        $data['hospital_override_mandate_details'] = $hospital_override_mandate_details;
        $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;

        if (Request::ajax()) {
            return Response::json($data['logs']);
        } else
            return View::make("physicians.rejectedLogs")->with($data);
    }

    public function reSubmitLog()
    {
        $physicianLog = new PhysicianLog();
        return $physicianLog->reSubmit(array(Request::input()), Auth::user()->id);
    }

    public function reSubmitEditLog()
    {
        $physicianLog = new PhysicianLog();
        return $physicianLog->reSubmitEditLog(array(Request::input()));
    }

    public function checkAgreementApproval($physician_id, $pid = 0, $agreement_id)
    {
        /* get Approval process status
            */
        $result = array();
        $approvalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $agreement_id)
            ->where('is_deleted', '=', '0')
            ->where('contract_id', "=", '0')
            ->orderBy('level')->get();
        $agreement = Agreement::findOrFail($agreement_id)->toArray();
        $agreemnet_approval_info = $approvalManagerInfo->toArray();
        $result_json = json_encode($result);
        return ['agreement' => $agreement, 'approvalManagerInfo' => $agreemnet_approval_info, 'agreement_start_date' => date('m/d/Y', strtotime($agreement['start_date'])), 'agreement_end_date' => date('m/d/Y', strtotime($agreement['end_date'])), 'agreement_valid_upto_date' => date('m/d/Y', strtotime($agreement['valid_upto']))];
    }

    //physician to multiple hospital by 1254 : issue fixed on manual end date not displaying.

    public function getDeleted()
    {

        $options = [
            'sort' => Request::input('sort', 7),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 7,
            'appends' => ['sort', 'order'],
            'field_names' => ['email', 'last_name', 'first_name', 'practice_name', 'hospital_name', 'password_text', 'created_at']
        ];

        // $data = $this->queryWithUnion('Physician', $options,function ($query, $options) {
        //     return $query
        //         ->select('physicians.id','physicians.email', 'physicians.last_name', 'physicians.first_name', 'physicians.password_text', 'practices.name as practice_name', 'hospitals.name as hospital_name', 'physicians.created_at')
        //         ->join("practices", "physicians.practice_id", "=", "practices.id")
        //         ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
        //        ->onlyTrashed();
        // });
        $data = $this->queryWithUnion('Physician', $options, function ($query, $options) {
            return $query
                ->select('physicians.id', 'physicians.email', 'physicians.last_name', 'physicians.first_name', 'physicians.password_text', 'practices.name as practice_name', 'hospitals.name as hospital_name', 'physicians.created_at', 'practices.id as practice_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join("practices", "physician_practices.practice_id", "=", "practices.id")
                ->join("hospitals", "practices.hospital_id", "=", "hospitals.id")
                ->whereNotNull('physician_practices.deleted_at')
                ->orderBy('physician_practices.deleted_at', 'desc')
                ->withTrashed();
        });

        $data['table'] = View::make('physicians/_trashPhysicians')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/index_restore')->with($data);
    }

    public function getRestore($id, $pid)
    {
        $result = Physician::getRestore($id, $pid);
        return $result;
    }

    public function interfaceDetails($id, $practice_id = 0)
    {


        $interfaceDetailsLawson = PhysicianInterfaceLawsonApcinvoice::where('physician_id', $id)->whereNull('deleted_at')->first();
        $data['interfaceType'] = 0;
        if (!$interfaceDetailsLawson) {
            $interfaceDetailsLawson = new PhysicianInterfaceLawsonApcinvoice();
        } else {
            $data['interfaceType'] = 1;
        }

        if ($data['interfaceType'] == 0) {
            $data['interfaceType'] = 1;
        }

        $data['physician'] = Physician::findOrFail($id);
        $data['interfaceDetailsLawson'] = $interfaceDetailsLawson;
        //USED TO CONTROL WHICH INTERFACE TYPES ARE AVAILABLE ON THE PHYSICIAN INTERFACE DETAILS FORM
        $data['interfaceTypes'] = InterfaceType::whereIn('id', [1])->pluck('name', 'id');

// physician to multiple hospital by 1254
        $practice = $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;
        return View::make('physicians/interfacedetails')->with($data);
    }

    //  physician to multiple hospital by 1254

    public function postInterfaceDetails($id, $practice_id = 0)
    {
        $physician = Physician::findOrFail($id);

        // $hospital = $physician->practice->hospital;
        $practice = Practice::findOrFail($practice_id);
        $hospital_id = $practice->hospital_id;
        $hospital = Hospital::findOrFail($hospital_id);

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new PhysicianInterfaceValidation();

        $interfaceType = Request::input("interface_type_id");

        if ($interfaceType == 1) {
            if (!$hospital->get_isInterfaceReady($hospital->id, 1)) {
                return Redirect::back()->with(['error' => Lang::get('physician_interface.hosp_lawson_interface_not_ready')])->withInput();
            }
            $physicianInterface = PhysicianInterfaceLawsonApcinvoice::where('physician_id', $id)->whereNull('deleted_at')->first();
            if ($physicianInterface) {

                if (!$validation->validateEdit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                $physicianInterface->cvi_company = intval(Request::input("cvi_company"));
                $physicianInterface->cvi_vendor = Request::input("cvi_vendor");
                $physicianInterface->cvi_auth_code = Request::input("cvi_auth_code");
                $physicianInterface->cvi_proc_level = Request::input("cvi_proc_level");
                $physicianInterface->cvi_sep_chk_flag = Request::input("cvi_sep_chk_flag");
                $physicianInterface->cvi_term_code = Request::input("cvi_term_code");
                $physicianInterface->cvi_rec_status = intval(Request::input("cvi_rec_status"));
                $physicianInterface->cvi_posting_status = intval(Request::input("cvi_posting_status"));
                $physicianInterface->cvi_bank_inst_code = Request::input("cvi_bank_inst_code");
                $physicianInterface->cvi_invc_ref_type = Request::input("cvi_invc_ref_type");
                $physicianInterface->updated_by = $this->currentUser->id;
                if (!$physicianInterface->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not update')]);
                } else {
                    // physician to multiple hospital by 1254
                    return Redirect::route('physicians.edit', [$physician->id, $practice_id])
                        ->with(['success' => Lang::get('physicians.edit_success')]);
                }
            } else {

                if (!$validation->validateCreate(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }

                $physicianInterfacenew = new PhysicianInterfaceLawsonApcinvoice();
                $physicianInterfacenew->physician_id = $physician->id;
                $physicianInterfacenew->cvi_company = intval(Request::input("cvi_company"));
                $physicianInterfacenew->cvi_vendor = Request::input("cvi_vendor");
                $physicianInterfacenew->cvi_auth_code = Request::input("cvi_auth_code");
                $physicianInterfacenew->cvi_proc_level = Request::input("cvi_proc_level");
                $physicianInterfacenew->cvi_sep_chk_flag = Request::input("cvi_sep_chk_flag");
                $physicianInterfacenew->cvi_term_code = Request::input("cvi_term_code");
                $physicianInterfacenew->cvi_rec_status = intval(Request::input("cvi_rec_status"));
                $physicianInterfacenew->cvi_posting_status = intval(Request::input("cvi_posting_status"));
                $physicianInterfacenew->cvi_bank_inst_code = Request::input("cvi_bank_inst_code");
                $physicianInterfacenew->cvi_invc_ref_type = Request::input("cvi_invc_ref_type");
                $physicianInterfacenew->created_by = $this->currentUser->id;
                $physicianInterfacenew->updated_by = $this->currentUser->id;
                if (!$physicianInterfacenew->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not save')]);
                } else {
                    // physician to multiple hospital by 1254
                    return Redirect::route('physicians.edit', [$physician->id, $practice_id])
                        ->with(['success' => Lang::get('physicians.edit_success')]);
                }
            }
        }

    }

    // physician to multiple hospital by 1254

    public function onetomanyphysicianreports_update()
    {
        if (!is_super_user())
            App::abort(403);

        $onetomany_update = PhysicianReport::updateExistingPhysiciansReportwithPracticeId();
    }

    //one-many physician : New function added to update practice_id in table 'physicianreports' by 1254

    public function getPhysicianHospitalReports($physician_id)
    {
        $default = [-1 => 'All'];
        $physician = Physician::findOrFail($physician_id);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $hospitals = Hospital::select('hospitals.*')
            ->join('physician_practices', 'physician_practices.hospital_id', '=', 'hospitals.id')
            ->where('physician_practices.physician_id', '=', $physician_id)
            ->pluck('name', 'id')->toArray();
        $default_hospital_key = key($hospitals);

        $agreements = Request::input("agreements", null);
        $hospital = Request::input("hospital", $default_hospital_key);
        $contractType = Request::input("contract_type", -1);
        $show_archived_flag = Request::input("show_archived");

        if ($show_archived_flag == 1) {
            $status1 = true;
        } else {
            $status1 = false;
        }

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('PhysicianHospitalReport', $options, function ($query, $options) use ($physician_id) {
            return $query->where('physician_id', '=', $physician_id)
                ->where("type", "=", 1);
        });

        $contract_types = $default + ContractType::getContractTypesByPhysician($physician->id, $hospital);
        $default_contract_key = key($contract_types);

        $physician->practice_id = Request::has("p_id") ? Request::Input("p_id") : 0;

        $data['hospitals'] = $hospitals;
        $data['physician'] = $physician;
        $data['check'] = 1;
        $data['showCheckbox'] = true;
        $data['isChecked'] = $status1;
        $data['report_id'] = Session::get('report_id');
        $data['table'] = View::make('physicians/_reports_table')->with($data)->render();
        $data["contract_type"] = Request::input("contract_type", $default_contract_key);
        $data['hospital'] = Request::input("hospital", $default_hospital_key);
        $data['form_title'] = "Generate Report";
        $data["contract_types"] = $contract_types;
        $data['agreements'] = Agreement::getPhysicianHospitalAgreementData($physician, $data["contract_type"], $hospital);

        $data['form'] = View::make('layouts/_reports_form_physician_hospital')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/physician_hospital_report')->with($data);
    }

    //end-one-many physician : New function added to update practice_id in table 'physicianreports' by 1254

    public function getContractTypesByPhysician($physicianId, $hospital, $check_report_type)
    {
        if (Request::ajax()) {
            if ($check_report_type == 0) {
                $default = [-1 => 'All'];
                $data['contract_types'] = $default + ContractType::getContractTypesByPhysician($physicianId, $hospital);
            } else {
                $data['contract_types'] = ContractType::getContractTypesByPhysician($physicianId, $hospital);
            }
            return $data;
        }
    }

    public function postPhysicianHospitalReports($physician_id)
    {
        $hospital_id = Request::input("hospital");
        $physician_hospital_id = Session::put('physician_hospital_id', $hospital_id);
        $hospital = Hospital::find($hospital_id);

        if (!is_physician_owner($physician_id))
            App::abort(403);

        $reportdata = HospitalReport::getReportData($hospital);
        return $reportdata;
    }

    public function getpaymentStatusReports($physician_id)
    {
        $default = [-1 => 'All'];
        $physician = Physician::findOrFail($physician_id);

        if (!is_physician_owner($physician->id))
            App::abort(403);

        $hospitals = Hospital::select('hospitals.*')
            ->join('physician_practices', 'physician_practices.hospital_id', '=', 'hospitals.id')
            ->where('physician_practices.physician_id', '=', $physician_id)
            ->pluck('name', 'id')->toArray();
        $default_hospital_key = key($hospitals);

        $hospital = Request::input("hospital", $default_hospital_key);
        $agreements = Request::input("agreements", null);
        $start_date = Request::input("start_date", null);
        $end_date = Request::input("end_date", null);
        $contract_type_change = Request::input("contract_type_change", null);
        $contractType = Request::input("contract_type", -1);

        $options = [
            'sort' => Request::input('sort', 2),
            'order' => Request::input('order', 2),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            'field_names' => ['filename', 'created_at']
        ];

        $data = $this->query('PhysicianHospitalReport', $options, function ($query, $options) use ($physician_id) {
            return $query->where('physician_id', '=', $physician_id)
                ->where("type", "=", 2);
        });

        $physician->practice_id = Request::has("p_id") ? Request::Input("p_id") : 0;
        $contract_types = ContractType::getContractTypesByPhysician($physician->id, $hospital);
        $default_contract_key = key($contract_types);
        $data['physician'] = $physician;
        $data['hospitals'] = $hospitals;
        $data['table'] = View::make('physicians/paymentStatus/_index')->with($data)->render();
        $data['report_id'] = Session::get('report_id');
        $data['contract_type'] = Request::input("contract_type", $default_contract_key);
        $data['hospital'] = Request::input("hospital", $default_hospital_key);
        $data['form_title'] = "Generate Report";
        $data['contract_types'] = $contract_types;

        $data['agreements'] = Agreement::getPhysicianHospitalAgreementData($physician, $data["contract_type"], $hospital);

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
        $data['form'] = View::make('layouts/_reports_form_physician_payment_status')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('physicians/paymentStatus/index')->with($data);
    }

    public function postpaymentStatusReports($physician_id)
    {
        if (!is_physician_owner($physician_id))
            App::abort(403);

        $hospital_id = Request::input("hospital");
        $physician_hospital_id = Session::put('physician_hospital_id', $hospital_id);
        $hospital = Hospital::findOrFail($hospital_id);

        $agreement_ids = Request::input('agreements');
        $physician_ids[] = $physician_id;

        $reportdata = HospitalReport::getPaymentStatusReportData($hospital, $agreement_ids, $physician_ids);
        return $reportdata;
    }

    public function getPhysicianHospitalReport($physician_id, $report_id)
    {
        $hospital_id = Session::get('physician_hospital_id'); // Request::input("hospital");
        $hospital = Hospital::findOrFail($hospital_id);

        $report = PhysicianHospitalReport::findOrFail($report_id);

        if (!is_physician_owner($physician_id))
            App::abort(403);

        $filename = hospital_report_path($hospital, $report);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
    }

    public function getphysicianHospitalDeleteReport($physician_id, $report_id)
    {
        $physician = Physician::findOrFail($physician_id);
        $report = PhysicianHospitalReport::findOrFail($report_id);

        if (!is_physician_owner($physician_id))
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

    public function postSortingContractNames()
    {
        $request = $_POST;

        if (Request::ajax()) {
            $data = SortingContractName::postSortingContractNames($request);
            return $data;
        }
    }

    public function update_approved_time_physician_log($hospital_id = 0)
    {
        ini_set('max_execution_time', 12000);

        $hospitals = Hospital::where('id', '=', $hospital_id)->distinct()->get();

        foreach ($hospitals as $hospital) {
            $agreements = Agreement::where('hospital_id', '=', $hospital->id)->pluck('id');
            $contracts = Contract::select('id', 'agreement_id', 'default_to_agreement')->whereIn('agreement_id', $agreements)->distinct()->get();

            foreach ($contracts as $contract) {
                if ($contract->default_to_agreement != 0) {
                    $approval_level = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->orderBy('level')->get();
                } else {
                    $approval_level = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)->where("contract_id", "=", $contract->id)->where("is_deleted", "=", '0')->orderBy('level')->get();
                }

                if (count($approval_level) > 0) {
                    $final_approver = $approval_level[count($approval_level) - 1];
                }

                $logs = PhysicianLog::where('contract_id', '=', $contract->id)->whereNull('physician_logs.deleted_at')->distinct()->get();

                if (count($logs) > 0) {
                    foreach ($logs as $log) {
                        $log_approval = LogApproval::where('log_id', '=', $log->id)->orderBy('approval_managers_level')->get();

                        if (count($log_approval) > 0) {
                            foreach ($log_approval as $approval_mgr) {
                                // log::info('$log->id', array($log->id));
                                PhysicianLog::update_time_to_approve($contract->agreement_id, $log->id, $approval_mgr->approval_managers_level);

                                // log::info('$final_approver->user_id', array($final_approver->user_id));
                                // log::info('$approval_mgr->user_id', array($approval_mgr->user_id));
                                if (count($approval_level) > 0) {
                                    if ($final_approver->user_id == $approval_mgr->user_id) {

                                        $start_date = date('Y-m-01', strtotime($log->date));
                                        $end_date = date('Y-m-t', strtotime($log->date));

                                        $amount_paid = Amount_paid::select("amount_paid.created_at")
//                                            ->where('amount_paid.physician_id', '=', $contract->physician_id)
                                            ->where('amount_paid.contract_id', '=', $contract->id)
                                            ->where('amount_paid.start_date', '>=', $start_date)
                                            ->where('amount_paid.end_date', '<=', $end_date)
                                            ->orderBy('id', 'asc')
                                            ->first();

                                        if ($amount_paid) {
                                            $datediff = strtotime($amount_paid->created_at) - strtotime($approval_mgr->approval_date);
                                            // log::info('$amount_paid->created_at', array($amount_paid->created_at));
                                            // log::info('$log_approval->approval_date', array($log_approval->approval_date));
                                            $total_days = round($datediff / (60 * 60 * 24));
                                            $physician_log = PhysicianLog::find($log->id);
                                            if ((int)$total_days < 0) {
                                                $total_days = 0;
                                            }
                                            $physician_log->time_to_payment = (int)$total_days;
                                            $physician_log->save();
                                        }
                                    }
                                } else {
                                    $start_date = date('Y-m-01', strtotime($log->date));
                                    $end_date = date('Y-m-t', strtotime($log->date));

                                    $amount_paid = Amount_paid::select("amount_paid.created_at")
//                                        ->where('amount_paid.physician_id', '=', $contract->physician_id)
                                        ->where('amount_paid.contract_id', '=', $contract->id)
                                        ->where('amount_paid.start_date', '>=', $start_date)
                                        ->where('amount_paid.end_date', '<=', $end_date)
                                        ->orderBy('id', 'asc')
                                        ->first();

                                    $approve_log_self = LogApproval::where('log_id', '=', $log->id)->where('approval_managers_level', '=', 0)->first();
                                    if (($amount_paid) && ($approve_log_self)) {
                                        $datediff = strtotime($amount_paid->created_at) - strtotime($approve_log_self->approval_date);
                                        // log::info('$amount_paid->created_at', array($amount_paid->created_at));
                                        // log::info('$log_approval->approval_date', array($log_approval->approval_date));
                                        $total_days = round($datediff / (60 * 60 * 24));
                                        $physician_log = PhysicianLog::find($log->id);
                                        if ((int)$total_days < 0) {
                                            $total_days = 0;
                                        }
                                        $physician_log->time_to_payment = (int)$total_days;
                                        $physician_log->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // log::info('Success');
        return 1;
    }

    public function getsortingcontractnames($practice_id, $physician_id)
    {
        // $request = $_POST;
        log::info('practice_id', array($practice_id));
        if (Request::ajax()) {
            log::info('practice_id', array($practice_id));
            log::info('physician_id', array($physician_id));
            $data = SortingContractName::getsortingcontractnames($practice_id, $physician_id);
            return $data;
        }
    }


    public function update_payment_status($hospital_id = 0, $agreement_id = 0, $contract_id = 0)
    {
        $result = PaymentStatusDashboard::updatePaymentStatus($hospital_id, $agreement_id, $contract_id);
        return $result;
    }

    public function getRecentLogs($physician_id, $contract_id)
    {
        // $physician = Physician::findOrFail($physician_id);

        if ($contract_id > 0) {
            $contract = Contract::findOrFail($contract_id);
            $recent_logs = PhysicianLog::getRecentLogs($contract, $physician_id);
            $data_recent_logs['recent_logs'] = $recent_logs;
            $data_recent_logs['hourly_summary'] = array();
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $data_recent_logs['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data_recent_logs["recent_logs"], $contract, $physician_id);
            }
            $data_recent_logs['recent_logs_count'] = count($recent_logs);
            $data_recent_logs['hourly_summary_count'] = count($data_recent_logs['hourly_summary']);
            $data_recent_logs['physician_id'] = $physician_id;
            $data_recent_logs['mandate_details'] = $contract->mandate_details == 1 ? true : false;
            $data['recent_logs_count'] = count($recent_logs);
            $data['recent_logs'] = $recent_logs;
            $data['statistics'] = PhysicianLog::getContractStatistics($contract);
            $data['priorMonthStatistics'] = PhysicianLog::getPriorContractStatistics($contract);
            $data['current_month_logs_days_duration'] = PhysicianLog::currentMonthLogDayDuration($contract);
            $data['payment_type_id'] = $contract->payment_type_id;
            $data['partial_hours'] = $contract->partial_hours;
            $data['recent_logs_view'] = View::make('physicians/subview_recent_logs')->with($data_recent_logs)->render();
        } else {
            $data[] = [];
        }

        if (Request::ajax()) {
            return Response::json($data);
        }

    }

    public function getApprovedLogs($physician_id, $contract_id)
    {
        // $physician = Physician::findOrFail($physician_id);
        if ($contract_id > 0) {
            $contract = Contract::findOrFail($contract_id);
            $agreement_data = Agreement::getAgreementData($contract->agreement);
            $data_approve_logs['contract'] = $contract;
            $approve_logs = PhysicianLog::getPhysicianApprovedLogs($contract, $physician_id);
            $data_approve_logs['results'] = $approve_logs;
            $data_approve_logs['hourly_summary'] = array();
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $data_approve_logs['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data_approve_logs["results"], $contract, $physician_id);
            }
            $data_approve_logs['approve_logs_count'] = count($approve_logs);
            $data_approve_logs['hourly_summary_count'] = count($data_approve_logs['hourly_summary']);
            $data_approve_logs['physician_id'] = $physician_id;
            $data_approve_logs['mandate_details'] = $contract->mandate_details == 1 ? true : false;
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

            $data_approve_logs['date_selectors'] = $renumbered;

            if (count($approve_logs) > 0) {
                $user_id = Auth::user()->id;
                $data_approve_logs['contract'] = $contract;

                $hospitals_override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
                    ->where("hospital_id", "=", $agreement_data->hospital_id)
                    ->where('is_active', '=', 1)
                    ->get();

                $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
                    ->where("hospital_id", "=", $agreement_data->hospital_id)
                    ->where('is_active', '=', 1)
                    ->get();

                $data_approve_logs['hospitals_override_mandate_details'] = $hospitals_override_mandate_details;
                $data_approve_logs['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;

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

                $data_approve_logs['payment_frequency_frequency'] = $period_label;

                $monthly_attestation_questions = [];
                $annually_attestation_questions = [];
                if ($contract->state_attestations_monthly) {
                    $monthly_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 1, $physician_id);
                }
                if ($contract->state_attestations_annually) {
                    $annually_attestation_questions = AttestationQuestion::getAnnuallyMonthlyAttestations($contract->id, 2, $physician_id);
                }

                $data_approve_logs['monthly_attestation_questions'] = count($monthly_attestation_questions) > 0 ? true : false;
                $data_approve_logs['annually_attestation_questions'] = count($annually_attestation_questions) > 0 ? true : false;

                $data_approve_logs['approve_logs_view'] = View::make('physicians/subview_approve_logs')->with($data_approve_logs)->render();
                return $data_approve_logs;
            } elseif (count($approve_logs) == 0 && $contract->payment_type_id == 4) {
                return "<div>No logs to approve.</div>";
            }
        } else {
            $data[] = [];
        }
    }

    private function contractActions($contract, $available, $used)
    {
        $results = [];

        foreach ($available as $item) {
            $data = new stdClass();
            $data->id = $item->id;
            $data->name = $item->name;
            $data->field = "action-{$data->id}-value";
            $data->checked = false;
            $data->hours = formatNumber(0.00);

            $action = $contract->actions()->where('action_id', '=', $item->id)->first();
            if ($action) {
                $data->checked = true;
                $data->hours = formatNumber($action->pivot->hours);
            }

            $results[] = $data;
        }

        return $results;
    }

    public function getDecryptedPassword($id)
    {
        $physician = Physician::findOrFail($id);
        return Response::json(['password_text' => $physician->getPasswordText()]);
    }

}
