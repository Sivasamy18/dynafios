<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use OwenIt\Auditing\Contracts\Auditable;
use App\Http\Controllers\Validations\PhysicianValidation;
use Request;
use Redirect;
use Lang;
use Hash;
use DateTime;
use Log;
use App\Services\EmailQueueService;
use App\Http\Controllers\Validations\EmailValidation;
use Illuminate\Support\Facades\Crypt;
use App\customClasses\EmailSetup;
use Swift_TransportException;
use Spatie\Permission\Traits\HasRoles;
use function App\Start\is_physician_owner;

class Physician extends Model implements Auditable
{

    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use HasRoles;
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    private static function contractTypes()
    {
        return options(ContractType::all(), 'id', 'name');
    }

    private static function getAgreements($hospital_id)
    {
        /* where clause of is_deleted added for soft delete
            Code modified_on: 07/04/2016
            */
        return Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where('is_deleted', '=', false)
            ->pluck('name', 'id', 'payment_frequency_type');
    }

    private static function getAgreementsPmtFrequency($hospital_id)
    {
        /* where clause of is_deleted added for soft delete
            Code modified_on: 07/04/2016
            */
        return Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where('is_deleted', '=', false)
            ->pluck('payment_frequency_type', 'id');
    }

    private static function paymentTypes()
    {
        return options(PaymentType::all(), 'id', 'name');
    }

    public function specialty()
    {
        return $this->belongsTo('App\Specialty');
    }

    public function practices()
    {
        return $this->belongsToMany(Practice::class, 'physician_practices', 'physician_id', 'practice_id');
    }


    /*below join with agreements is added for purpose of soft delete of agreement
               Code modified_on: 08/04/2016
               */
    public function activeContracts($practice_id = 0)
    {
        return $this->hasMany('App\Contract')
            ->select(['*', 'contracts.id as contract_id'])
            ->join("agreements", "contracts.agreement_id", "=", "agreements.id")
            ->where('contracts.archived', '=', false)
            ->where('contracts.manually_archived', '=', false)/*added for remove manually archived contracts on 12 Dec 2019*/
            ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
            ->where('contracts.practice_id', '=', $practice_id)
            ->where('agreements.is_deleted', '=', 0)->get();
    }

    public function contracts()
    {
//        return $this->hasMany('App\Contract')
//        ->where('agreements.is_deleted', '=', 0);

        return $this->belongsToMany('App\Contract', 'physician_contracts', 'physician_id', 'contract_id')
            ->whereNull('physician_contracts.deleted_at')
            ->withPivot('physician_id', 'contract_id', 'practice_id', 'created_by', 'updated_by')->withTimestamps();
    }

    public function apiContracts()
    {
//        return $this->hasMany('App\Contract');
        return $this->belongsToMany('App\Contract', 'physician_contracts', 'physician_id', 'contract_id')
            ->whereNull('physician_contracts.deleted_at')
            ->withPivot('physician_id', 'contract_id', 'practice_id', 'created_by', 'updated_by')->withTimestamps();
    }

    public function logs()
    {
        return $this->hasMany('App\PhysicianLog');
    }

    public function actions()
    {
        return $this->belongsToMany('App\Action');
    }

    public function reports()
    {
        return $this->hasMany("App\PhysicianReport");
    }

    public function accessTokens()
    {
        return $this->hasMany("App\AccessToken");
    }

    public function payments()
    {
        return $this->hasMany("App\PhysicianPayment");
    }

    public function recentLogs($count = 10, $practice_id = 0)
    {
        return $this->hasMany('App\PhysicianLog')
            ->join("contracts", function ($join) {
                $join->on("physician_logs.physician_id", "=", "contracts.physician_id")
                    ->on("physician_logs.practice_id", "=", "contracts.practice_id");
            })
            ->where("contracts.archived", "=", '0')
            ->where("contracts.end_date", "=", "0000-00-00 00:00:00")
            ->where("physician_logs.practice_id", "=", $practice_id)
            //->orderBy('physician_logs.date', 'desc')->take($count);
            ->orderBy('physician_logs.date', 'desc')->limit($count)->get();
    }

    public function getFullName($reverse = false)
    {
        return $reverse ? "{$this->last_name}, {$this->first_name}" :
            "{$this->first_name} {$this->last_name}";
    }

    public function getHolidays($year = '')
    {
        return $this->holidays($year);
    }

    public static function createPhysician($practice_id)
    {
        $practice = Practice::findOrFail($practice_id);

        // checking invoice type for validation
        $result_hospital = Hospital::findOrFail($practice->hospital_id);

        $validation = new PhysicianValidation();
        $emailvalidation = new EmailValidation();
        if ((!$validation->validateCreate(Request::input()))) {
            if ($validation->messages()->has('email') && Request::input('email') != '') {
                $deletedUser = Physician::where('email', '=', trim(Request::input('email')))->onlyTrashed()->first();
                if ($deletedUser) {
                    $validation->messages()->add('emailDeleted', 'Physician with this email already exist, you can request administrator to restore it.');
                } else {
                    $deletedUser = User::where('email', '=', trim(Request::input('email')))->onlyTrashed()->first();
                    if ($deletedUser) {
                        $validation->messages()->add('emailDeleted', 'Physician with this email already exist, you can request administrator to restore it.');
                    }
                }
            }
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
        }


        $datetime = Request::input('practice_start_date');
        $test_arr = explode('/', $datetime);
        if ($datetime != '' && count($test_arr) == 3) {
            if (checkdate($test_arr[0], $test_arr[1], $test_arr[2])) {
                if (strtotime($datetime) <= strtotime(date("Y-m-d"))) {

                    DB::beginTransaction(); /*begin transaction*/

                    try {
                        $randomPassword = randomPassword();
                        $physician = new Physician();
                        //$physician->practice_id = $practice->id;                  //drop column practice_id from table 'physicians' changes by 1254
                        $physician->specialty_id = Request::input('specialty');
                        $physician->email = Request::input('email');
                        $physician->first_name = Request::input('first_name');
                        $physician->last_name = Request::input('last_name');
                        $physician->npi = Request::input('npi');
                        $physician->phone = Request::input('phone');
                        //$physician->password_text = randomPassword();
                        $physician->setPasswordText($randomPassword);
                        $physician->password = Hash::make($randomPassword);
                        // $physician->physician_type = Request::input('physician_type');

                        if (!$physician->save()) {
                            DB::rollback();
                            return Redirect::back()->with([
                                'error' => Lang::get('practices.create_physician_error')
                            ]);
                        } else {
                            $end_date = mysql_date("2037-12-31 00:00:00");
                            $PhysicianPracticeHistory = new PhysicianPracticeHistory();
                            $PhysicianPracticeHistory->practice_id = $practice->id;
                            $PhysicianPracticeHistory->physician_id = $physician->id;
                            $PhysicianPracticeHistory->specialty_id = $physician->specialty_id;
                            $PhysicianPracticeHistory->email = $physician->email;
                            $PhysicianPracticeHistory->first_name = $physician->first_name;
                            $PhysicianPracticeHistory->last_name = $physician->last_name;
                            $PhysicianPracticeHistory->npi = $physician->npi;
                            $PhysicianPracticeHistory->created_at = date('Y-m-d H:i:s');
                            $PhysicianPracticeHistory->start_date = mysql_date($datetime . ' 00:00:00');
                            $PhysicianPracticeHistory->end_date = $end_date;
                            // $physician->physician_type = Request::input('physician_type');

                            if (!$PhysicianPracticeHistory->save()) {
                                DB::rollback();
                                return Redirect::back()->with([
                                    'error' => Lang::get('practices.create_physician_error')
                                ]);
                            }

                            $user = new User();
                            $user->email = $physician->email;
                            $user->first_name = $physician->first_name;
                            $user->last_name = $physician->last_name;
                            $user->initials = strtoupper("{$user->first_name[0]}{$user->last_name[0]}");
                            $user->phone = $physician->phone;
                            $user->group_id = Group::Physicians;
                            $user->password_text = $physician->password_text;
                            $user->password = Hash::make($randomPassword);
                            $user->seen_at = date("Y-m-d H:i:s");

                            $hospital_password_expiration_months = DB::table("practices")
                                ->select("hospitals.password_expiration_months as password_expiration_months")
                                ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
                                ->where("practices.id", "=", $practice->id)
                                ->pluck('password_expiration_months');
                            //$expiration_date = new DateTime("+".max($hospital_password_expiration_months)." months");
                            $expiration_date = new DateTime('1999-01-01');
                            if (!$expiration_date) {
                                $expiration_date = new DateTime("+12 months");
                            }
                            $user->password_expiration_date = $expiration_date;

                            if (!$user->save()) {
                                DB::rollback();
                                return Redirect::back()->with([
                                    'error' => Lang::get('practices.create_physician_error')
                                ]);
                            }
                            //add new physician to physician_practices table by #1254

                            $physician_practices = new PhysicianPractices();
                            $physician_practices->practice_id = $practice_id;
                            $physician_practices->hospital_id = $practice->hospital_id;
                            $physician_practices->physician_id = $physician->id;
                            $physician_practices->start_date = mysql_date($datetime . ' 00:00:00');
                            $physician_practices->end_date = $end_date;
                            $physician_practices->save();
                        }


                        /*add invoice notes for physician*/
                        if (Request::input('note_count') > 0) {
                            $index = 1;
                            for ($i = 1; $i <= Request::input('note_count'); $i++) {
                                if (Request::input("note" . $i) != '') {
                                    $invoice_note = new InvoiceNote();
                                    $invoice_note->note_type = InvoiceNote::PHYSICIAN;
                                    $invoice_note->note_for = $physician->id;
                                    $invoice_note->note_index = $index;
                                    $invoice_note->note = Request::input("note" . $i);
                                    $invoice_note->is_active = true;
                                    $invoice_note->hospital_id = $result_hospital->id;
                                    $invoice_note->practice_id = $practice->id;
                                    $invoice_note->save();
                                    $index++;
                                }
                            }
                        }

                        DB::commit();

                        return Redirect::route('practices.physicians', $practice->id)->with([
                            'success' => Lang::get('practices.create_physician_success')
                        ]);
                    } catch (Exception $e) {
                        DB::rollback();
                        return Redirect::back()->with([
                            'error' => Lang::get('practices.create_physician_error')
                        ]);
                    }
                } else {
                    return Redirect::back()->with([
                        'error' => Lang::get('Practice start date should be less than or equal to current date.')
                    ]);
                }
            } else {
                return Redirect::back()->with([
                    'error' => Lang::get('Please select valid practice start date.')
                ]);
            }
        } else {
            return Redirect::back()->with([
                'error' => Lang::get('Please select practice start date.')
            ]);
        }
    }

    public function setPasswordText($value)
    {
        $this->attributes['password_text'] = $value;
        // $this->attributes['password_text'] = Crypt::encryptString($value);
    }

    public function getPasswordText()
    {
        return $this->attributes['password_text'];
        // return Crypt::decryptString($this->attributes['password_text']);
    }

    //One to Mant by 1254
    public static function addPhysicianToPractice($practice_id, $physician_email)
    {
        $practice_new = Practice::findOrFail($practice_id);
        $hospital_new = $practice_new->hospital_id;

        //2201

        //get physician id from email
        // $physician_id = Physician::where("email","=",$physician_email)->first();
        $physician_id = DB::table("physicians")
            ->select('id')
            ->where("email", "=", $physician_email)
            ->first();

        //drop column practice_id from table 'physicians' changes by 1254 : codereview
//        $existphysician = PhysicianPractices::select("physician_practices.*")
//                            ->where("physician_practices.hospital_id","=", $hospital_new)
//                            ->where("physician_practices.physician_id","=",  $physician_id->id)
//                            //issue fixed to change practice to previous hospital showing error msg
//                            ->whereRaw("physician_practices.start_date <= now()")
//                            ->whereRaw("physician_practices.end_date >= now()")
//                            ->whereNull("deleted_at")
//                            ->orderBy("start_date", "desc")
//                            ->get();
//
//        if($existphysician->isEmpty())
//        {
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $practices = DB::table("physician_practices")
            ->select("practice_id")
            ->where("physician_practices.physician_id", "=", $physician_id->id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            ->orderBy("start_date", "desc")
            ->get();

        $physcianHospitalflag = 0;
        $physician_hospital_id = 0;
        if (count($practices) > 0) {
            foreach ($practices as $practices) {
                $hospital_id = DB::table("practices")->select("practices.hospital_id")
                    ->where("practices.id", "=", $practices->practice_id)
                    ->pluck("practices.hospital_id")->toArray();


//                    if($hospital_id==$hospital_new)
//                    {
//                        $physcianHospitalflag=1;
//                    } else {
//                        $physician_hospital_id = $hospital_id;
//                    }
            }
        }

        $datetime = Request::Input('practice_start_date');


        if ($physcianHospitalflag == 0) {
            $physician_practices = new PhysicianPractices();
            $physician_practices->physician_id = $physician_id->id;
            $physician_practices->practice_id = $practice_id;
            $physician_practices->hospital_id = $practice_new->hospital_id;
            $physician_practices->start_date = mysql_date($datetime . ' 00:00:00');
            $physician_practices->end_date = mysql_date("2037-12-31 00:00:00");


            //add details into physician_practice_history table

            $physicianpracticehistory_old = DB::table("physician_practice_history")
                ->select("physician_practice_history.*")
                ->where("physician_practice_history.physician_id", "=", $physician_id->id)
                ->first();


            $physicianpracticehistory_new = new PhysicianPracticeHistory();

            $physicianpracticehistory_new->practice_id = $practice_id;
            $physicianpracticehistory_new->physician_id = $physician_id->id;
            $physicianpracticehistory_new->specialty_id = $physicianpracticehistory_old->specialty_id;
            $physicianpracticehistory_new->email = $physician_email;
            $physicianpracticehistory_new->first_name = $physicianpracticehistory_old->first_name;
            $physicianpracticehistory_new->last_name = $physicianpracticehistory_old->last_name;
            $physicianpracticehistory_new->npi = $physicianpracticehistory_old->npi;
            $physicianpracticehistory_new->created_at = date('Y-m-d H:i:s');
            $physicianpracticehistory_new->start_date = mysql_date($datetime . '00:00:00');
            $physicianpracticehistory_new->end_date = mysql_date("2037-12-31 00:00:00");
            $physicianpracticehistory_new->save();


            if (!$physician_practices->save()) {
                return ["status" => false];
            }
        }
        return ["status" => true];
//        }
//        else
//        {
//            return ["status" => false];
//        }
    }

    public static function editPhysician($id, $practice)
    {
        $physician = Physician::findOrFail($id);
        $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
        $physician_practice_history = PhysicianPracticeHistory::select('*')->where("physician_id", $id)->get();

        // checking invoice type for validation
        $physician_practice_detail = PhysicianPractices::where('physician_id', '=', $id)->where('practice_id', '=', $practice->id)->first();
        $result_hospital = Hospital::findOrFail($physician_practice_detail->hospital_id);
        $validation = new PhysicianValidation();
        $emailvalidation = new EmailValidation();
        if ($physician->email != Request::input('email') && $physician->npi != Request::input('npi')) {
            if (!$validation->validateEdit(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
        } elseif ($physician->email != Request::input('email') && $physician->npi == Request::input('npi')) {
            if (!$validation->validateEmailEdit(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
        } elseif ($physician->email === Request::input('email') && $physician->npi != Request::input('npi')) {
            if (!$validation->validateNpiEdit(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
        } elseif ($physician->email === Request::input('email') && $physician->npi == Request::input('npi')) {
            if (!$validation->validateOtherEdit(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
        }
        if (!$emailvalidation->validateEmailDomain(Request::input())) {
            return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
        }

        $physician->specialty_id = Request::input('specialty');
        $physician->email = Request::input('email');
        $physician->first_name = Request::input('first_name');
        $physician->last_name = Request::input('last_name');
        $physician->npi = Request::input('npi');
        $physician->phone = Request::input('phone');

        $user->email = $physician->email;
        $user->first_name = $physician->first_name;
        $user->last_name = $physician->last_name;
        $user->phone = $physician->phone;
        $user->locked = Request::input('locked');
        if ($user->locked == 0) {
            $user->unsuccessful_login_attempts = 0;
        }
        // $physician->physician_type = Request::input('physician_type');

        /* $physician_practice_history->email = $physician->email;
         $physician_practice_history->first_name = $physician->first_name;
         $physician_practice_history->last_name = $physician->last_name;*/


        //$physician_practice_history=array();

        if (count($physician_practice_history) > 0) {
            foreach ($physician_practice_history as $physician_practice_history_record) {
                /* $physician_practice_history->email[]= $physician->email;*/
                $physician_practice_history_record->first_name = $physician->first_name;
                $physician_practice_history_record->last_name = $physician->last_name;
                $physician_practice_history_record->email = $physician->email;

                /*$physician_practice_history->last_name[]= $physician->last_name;*/
                if (!$physician_practice_history_record->save()) {
                    return Redirect::back()->with([
                        'error' => Lang::get('physicians.edit_error')
                    ]);
                }
            }
        }


        if (!$user->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.edit_error')
            ]);
        } elseif (!$physician->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.edit_error')
            ]);
        }

        /*update Physician notes*/
        $index = 1;
        if (Request::input('note_count') > 0) {
            for ($i = 1; $i <= Request::input('note_count'); $i++) {
                if (Request::input("note" . $i) != '') {
                    $invoice_note_old = InvoiceNote::where("note_type", '=', InvoiceNote::PHYSICIAN)
                        ->where("note_for", '=', $physician->id)
                        ->where("note_index", '=', $index)
                        ->where("is_active", '=', true)
                        ->where("hospital_id", '=', $result_hospital->id)
                        ->where("practice_id", '=', $practice->id)
                        ->update(["note" => Request::input("note" . $i)]);
                    if (!$invoice_note_old) {
                        $invoice_note = new InvoiceNote();
                        $invoice_note->note_type = InvoiceNote::PHYSICIAN;
                        $invoice_note->note_for = $physician->id;
                        $invoice_note->note_index = $index;
                        $invoice_note->note = Request::input("note" . $i);
                        $invoice_note->is_active = true;
                        $invoice_note->hospital_id = $result_hospital->id;
                        $invoice_note->practice_id = $practice->id;
                        $invoice_note->save();
                    }
                    $index++;
                }
            }
        }
        InvoiceNote::where("note_type", '=', InvoiceNote::PHYSICIAN)
            ->where("note_for", '=', $physician->id)
            ->where("note_index", '>=', $index)
            ->where("is_active", '=', true)
            ->where("hospital_id", '=', $result_hospital->id)
            ->where("practice_id", '=', $practice->id)
            ->update(["is_active" => false]);

        return Redirect::back()->with([
            'success' => Lang::get('physicians.edit_success')
        ]);
    }

    /**
     * Lists the physicians with the specified agreements and contract types.
     *
     * @param $agreements   an array of agreements
     * @param $contractType the contract type
     */

    public static function getActivePhysicians($type = 'co-management')
    {
        $active_physicians = self::select(DB::raw("distinct(physicians.email) as email,physicians.last_name as name"))
            ->join("contracts", "physicians.id", "=", "contracts.physician_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");
        if ($type != 'co-management') {
            $active_physicians = $active_physicians->where("contracts.contract_type_id", "=", ContractType::MEDICAL_DIRECTORSHIP);
        } else {
            $active_physicians = $active_physicians->where("contracts.contract_type_id", "=", ContractType::CO_MANAGEMENT);
        }

        $active_physician = json_decode($active_physicians->get());

        for ($i = 0; $i < count($active_physician); $i++) {
            $data['name'] = $active_physician[$i]->name;
            $data['email'] = $active_physician[$i]->email;

            try {
                if ($type != 'co-management') {
                    $data['type'] = EmailSetup::LOG_REMINDER_FOR_PHYSICIAN_DIRECTORSHIP;
                    $data['with'] = [
                        'name' => $active_physician[$i]->name
                    ];

                    EmailQueueService::sendEmail($data);
                } else {
                    $data['type'] = EmailSetup::LOG_REMINDER_FOR_PHYSICIANS;
                    $data['with'] = [
                        'name' => $active_physician[$i]->name
                    ];

                    EmailQueueService::sendEmail($data);
                }
                sleep(5);
            } catch (Swift_TransportException $e) {
                Mail::getTransport()->stop();
                sleep(20); // Just in case ;-)
            }
        }
    }

    public static function listByAgreements($agreements, $contractType = -1, $show_deleted_physicians = false)
    {
        if (empty($agreements)) {
            return [];
        }

        $query = self::select(
            DB::raw("physicians.id as id"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as name")
        )
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->whereIn("contracts.agreement_id", $agreements)
            ->whereNull('contracts.deleted_at')
            ->orderBy("physicians.last_name")
            ->orderBy("physicians.first_name");

        if ($contractType != -1) {
            $query->where("contracts.contract_type_id", "=", $contractType);
        }
        /*added for soft delete*/
        if ($show_deleted_physicians) {
            $query->withTrashed();
        }

        return $query->pluck("name", "id");
    }

    public static function listByPracticeAgreements($agreements, $contractType = -1, $practice_id)
    {
        if (empty($agreements)) {
            return [];
        }
        $query = self::select(
            DB::raw("physicians.id as id"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as name")
        )
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->whereIn("contracts.agreement_id", $agreements)
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->where("physician_practices.practice_id", "=", $practice_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereNull("physician_practices.deleted_at")
            //->where("physicians.practice_id", "=", $practice_id)
            ->orderBy("physicians.last_name")
            ->orderBy("physicians.first_name");
        if ($contractType != -1) {
            $query->where("contracts.contract_type_id", "=", $contractType);
        }
        return $query->pluck("name", "id");
    }

    private function holidays($year)
    {
        $list = array();
        // New Year's Day (January 1)
        $currentDate = date('m/d/Y');
        $currentMonth = date("m");
        if ($year != '') {
            $currentYear = $year;
        } else {
            $currentYear = date("Y");
        }
        $currentDay = date("d");
        // check if New Year's Day (January 1)
        $list[] = "01/01/" . $currentYear;
        // check if Independence Day (4th of July)
        $list[] = "07/04/" . $currentYear;
        // check Memorial Day (last Monday of May)
        $lastMonday = strtotime("last Monday of May " . $currentYear);
        $list[] = date('m/d/Y', $lastMonday);
        // check Labor Day (1st Monday of September)
        $firstMondayCurrent = strtotime("first Monday of September " . $currentYear);
        $list[] = date('m/d/Y', $firstMondayCurrent);
        $firstMondayPrev = strtotime("first Monday of September " . ($currentYear - 1));
        $list[] = date('m/d/Y', $firstMondayPrev);
        // check Thanksgiving (4th Thursday of November)
        $fourthThursdayCurrent = strtotime("fourth Thursday of November " . $currentYear);
        $list[] = date('m/d/Y', $fourthThursdayCurrent);
        $fourthThursdayPrev = strtotime("fourth Thursday of November " . ($currentYear - 1));
        $list[] = date('m/d/Y', $fourthThursdayPrev);
        // check if Christmas (December 25)
        $list[] = "12/25/" . $currentYear;
        $list[] = "12/25/" . ($currentYear - 1);
        return $list;
    }

    public static function getPhysicianData($hospital, $agreements, $contract_type = -1, $start_date, $end_date, $show_deleted_physicians = false)
    {
        if ($agreements == null) {
            return [];
        }

        $query = self::select(
        //DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("physicians.first_name as first_name"),
            DB::raw("physicians.last_name as last_name"),
            DB::raw("contracts.id as contract_id")
        )
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            //drop column practice_id from table 'physicians' changes by 1254 :updated with physician_practices

            //->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join("practices", "practices.id", "=", "physician_practices.practice_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            //->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            // ->where("hospitals.id", "=", $hospital->id)
            ->whereIn("contracts.agreement_id", $agreements)
            ->whereNull("contracts.deleted_at")
            ->where("physician_practices.hospital_id", "=", $hospital->id)
            //->orderBy("practices.name", "asc")
            ->orderBy("physicians.last_name", "asc")
            ->orderBy("physicians.first_name", "asc");

        if ($contract_type != -1) {
            $query->where("contracts.contract_type_id", "=", $contract_type);
        }
        /*added for soft delete*/
        if ($show_deleted_physicians) {
            $query->withTrashed();
        }

        //physician to multiple hospital by 1254
        $results = $query->distinct()->get();

        $data = [];
        foreach ($results as $result) {
            $currentPractices = DB::table("practices")->select(
                DB::raw("practices.id"),
                DB::raw("practices.name")
            )
                ->join("physician_contracts", "physician_contracts.practice_id", "=", "practices.id")
                ->join("physician_practices", "physician_practices.practice_id", "=", "physician_contracts.practice_id")
                ->where("physician_contracts.physician_id", $result->physician_id)
                ->where('physician_contracts.contract_id', '=', $result->contract_id)
                ->where("physician_practices.hospital_id", "=", $hospital->id)
                ->get();

            foreach ($currentPractices as $currentPractice) {
                $practice_name = $currentPractice->name;
            }
            $checkContractquery = DB::table("contracts")->select(
                DB::raw("contracts.id"),
                DB::raw("contracts.agreement_id"),
                DB::raw("contracts.created_at"),
                DB::raw("contracts.end_date"),
                DB::raw("contracts.manual_contract_end_date")
            )
                ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                ->whereIn("contracts.agreement_id", $agreements)
                ->where("physician_contracts.physician_id", $result->physician_id)
                ->where("contracts.end_date", '!=', "0000-00-00 00:00:00")
                ->where("contracts.manual_contract_end_date", '!=', "0000-00-00")
                ->get();

            if (count($checkContractquery) > 0) {
                $checkAllContractquery = DB::table("contracts")->select(
                    DB::raw("contracts.id"),
                    DB::raw("contracts.agreement_id"),
                    DB::raw("contracts.created_at"),
                    DB::raw("contracts.end_date"),
                    DB::raw("contracts.manual_contract_end_date")
                )
                    ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                    ->whereIn("contracts.agreement_id", $agreements)
                    ->where("physician_contracts.physician_id", $result->physician_id)
                    ->get();
                foreach ($checkAllContractquery as $contract) {
                    if ($contract->end_date != "0000-00-00 00:00:00" || $contract->manual_contract_end_date != "0000-00-00") {
                        $start = explode(": ", $start_date[$contract->agreement_id]);
                        $end = explode(": ", $end_date[$contract->agreement_id]);
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
                        $contractDateEnd = date('Y-m-d', strtotime($contractEndDate[0]));
                        //echo $paymentDate; // echos today!
                        $reportDateBegin = date('Y-m-d', strtotime($start[1]));
                        $reportDateEnd = date('Y-m-d', strtotime($end[1]));

                        if (($contractDateEnd > $reportDateBegin) && ($contractDateEnd < $reportDateEnd)) {
                            if ($contract->end_date != "0000-00-00 00:00:00") {
                                $oldPractices = DB::table("practices")->select(
                                    DB::raw("practices.id"),
                                    DB::raw("practices.name")
                                )
                                    ->join("physician_practice_history", "practices.id", "=", "physician_practice_history.practice_id")
                                    ->join("physician_contracts", "physician_contracts.physician_id", "=", "physician_practice_history.physician_id")
                                    ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
                                    ->whereIn("contracts.agreement_id", $agreements)
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

    public static function getApprovalUserPhysician($user_id, $selected_manager, $practices, $selected_practice)
    {
        $default = ['0' => 'All'];
        $practice_ids = array_keys($practices);
        if ($selected_practice == 0) {
            $practice_ids = array_keys($practices);
        } else {
            $practice_ids[] = $selected_practice;
        }
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        if ($selected_manager != -1) {
            $physician = self::select('physicians.id as id', DB::raw(("concat(physicians.last_name, ', ', physicians.first_name) as name")))
                ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
                ->join('physician_contracts', function ($join) {
                    $join->on('physician_contracts.physician_id', '=', 'physician_practices.physician_id');
                    $join->on('physician_contracts.practice_id', '=', 'physician_practices.practice_id');

                })
                ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->where('agreements.archived', '=', false)
                ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0')
                //Physician to multiple hospital by 1254 : added to display existing phy in physician dropdown
                ->whereIn("physician_practices.practice_id", $practice_ids)
                // ->whereIn('physicians.practice_id', $practice_ids)
                ->orderBy("physicians.last_name")
                ->distinct()
                ->pluck('name', 'id');
        } else {
            $physician = self::select('physicians.id as id', DB::raw(("concat(physicians.last_name, ', ', physicians.first_name) as name")))
                ->join('physician_contracts', function ($join) {
                    $join->on('physician_contracts.physician_id', '=', 'physicians.id');

                })
                ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->where('agreements.archived', '=', false)
                ->whereIn("physician_contracts.practice_id", $practice_ids)
                ->whereNull('physician_contracts.deleted_at')
                ->orderBy("physicians.last_name")
                ->distinct()
                ->pluck('name', 'id');
        }

        $physicians_list = $physician;
        return $default + $physicians_list->toArray();

    }

    //Chaitraly::Physician list for performance Dashboard
    public static function getPhysician($user_id, $selected_hospital, $hospital, $selected_agreement, $agreements, $practices, $selected_practice)
    {

        $practice_ids = array_keys($practices);

        $agreement_ids = array_keys($agreements);

        $physician = self::select('physicians.id as id', DB::raw(("concat(physicians.last_name, ', ', physicians.first_name) as name")))
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            //->join("contracts", "contracts.physician_id", "=", "physicians.id")
            //->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereIn("agreements.id", $agreement_ids)
            ->whereIn("physician_practices.practice_id", $practice_ids)
            ->whereNotIn("contracts.payment_type_id", [3, 5]) //new condition added for per diem logs
            ->where('agreements.archived', '=', false)
            ->orderBy("physicians.last_name")
            ->distinct()
            ->pluck('name', 'id');
        $physicians_list = $physician;
        return $physicians_list->toArray();
    }

    public static function getApprovalUserTimePeriod($user_id)
    {
        $timePeriod = Contract::select('contracts.*')
            ->join("physicians", "physicians.practice_id", "=", "practices.id")
            //->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
            ->where('agreement_approval_managers_info.user_id', '=', $user_id)
            ->get();

        //Log::info('My contracts are:', array($timePeriod));
        return $timePeriod;
    }

    public static function getHolidaysForperiod($startyear, $start_date, $end_date, $endyear)
    {
        $list = array();
        $result = array();
        $week = 0;
        $weekend = 0;
        // New Year's Day (January 1)
        // check if New Year's Day (January 1)
        if (strtotime("01/01/" . $startyear) >= strtotime($start_date) && strtotime("01/01/" . $startyear) <= strtotime($end_date)) {
            $list[] = "01/01/" . $startyear;
            if (date("w", strtotime("01/01/" . $startyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        } elseif (strtotime("07/04/" . $endyear) >= strtotime($start_date) && strtotime("01/01/" . $endyear) <= strtotime($end_date)) {
            $list[] = "01/01/" . $endyear;
            if (date("w", strtotime("01/01/" . $endyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        }
        // check if Independence Day (4th of July)
        if (strtotime("07/04/" . $startyear) >= strtotime($start_date) && strtotime("07/04/" . $startyear) <= strtotime($end_date)) {
            $list[] = "07/04/" . $startyear;
            if (date("w", strtotime("07/04/" . $startyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        } elseif (strtotime("07/04/" . $endyear) >= strtotime($start_date) && strtotime("07/04/" . $endyear) <= strtotime($end_date)) {
            $list[] = "07/04/" . $endyear;
            if (date("w", strtotime("07/04/" . $endyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        }
        // check Memorial Day (last Monday of May)
        if (strtotime("last Monday of May " . $startyear) >= strtotime($start_date) && strtotime("last Monday of May " . $startyear) <= strtotime($end_date)) {
            $lastMonday = strtotime("last Monday of May " . $startyear);
            $list[] = date('m/d/Y', $lastMonday);
            $week++;
        } elseif (strtotime("last Monday of May " . $endyear) >= strtotime($start_date) && strtotime("last Monday of May " . $endyear) <= strtotime($end_date)) {
            $lastMonday = strtotime("last Monday of May " . $endyear);
            $list[] = date('m/d/Y', $lastMonday);
            $week++;
        }

        // check Labor Day (1st Monday of September)
        if (strtotime("first Monday of September " . $startyear) >= strtotime($start_date) && strtotime("first Monday of September " . $startyear) <= strtotime($end_date)) {
            $firstMondayCurrent = strtotime("first Monday of September " . $startyear);
            $list[] = date('m/d/Y', $firstMondayCurrent);
            $week++;
        } elseif (strtotime("first Monday of September " . $endyear) >= strtotime($start_date) && strtotime("first Monday of September " . $endyear) <= strtotime($end_date)) {
            $firstMondayCurrent = strtotime("first Monday of September " . $endyear);
            $list[] = date('m/d/Y', $firstMondayCurrent);
            $week++;
        }
        // check Thanksgiving (4th Thursday of November)
        if (strtotime("fourth Thursday of November " . $startyear) >= strtotime($start_date) && strtotime("fourth Thursday of November " . $startyear) <= strtotime($end_date)) {
            $fourthThursdayCurrent = strtotime("fourth Thursday of November " . $startyear);
            $list[] = date('m/d/Y', $fourthThursdayCurrent);
            $week++;
        } elseif (strtotime("fourth Thursday of November " . $endyear) >= strtotime($start_date) && strtotime("fourth Thursday of November " . $endyear) <= strtotime($end_date)) {
            $fourthThursdayCurrent = strtotime("fourth Thursday of November " . $endyear);
            $list[] = date('m/d/Y', $fourthThursdayCurrent);
            $week++;
        }
        // check if Christmas (December 25)
        if (strtotime("12/25/" . $startyear) >= strtotime($start_date) && strtotime("12/25/" . $startyear) <= strtotime($end_date)) {
            $list[] = "12/25/" . $startyear;
            if (date("w", strtotime("12/25/" . $startyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        } elseif (strtotime("12/25/" . $endyear) >= strtotime($start_date) && strtotime("12/25/" . $endyear) <= strtotime($end_date)) {
            $list[] = "12/25/" . $endyear;
            if (date("w", strtotime("12/25/" . $endyear)) < 6) {
                $week++;
            } else {
                $weekend++;
            }
        }
        $result['week'] = $week;
        $result['weekend'] = $weekend;
        return $result;
    }

    public static function getCreateContract($physician, $practice_id = 0)
    {
        $practice = Practice::findOrFail($practice_id);
        $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            //->where('hospital_user.hospital_id', '=', $physician->practice->hospital_id)
            ->where('hospital_user.hospital_id', '=', $practice->hospital_id)
            ->where('group_id', '!=', Group::Physicians)
            ->orderBy("name")
            ->pluck('name', 'id');
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[0] = "NA";

        //$practice = Practice::findOrFail($practice_id);
        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        //lar-8 changes by 1254
        $practice = Practice::findOrFail($practice_id);
        if ($practice_id > 0) {

            $data['practice'] = $practice;
            //drop column practice_id from table 'physicians' changes by 1254
            //$physician->practice->id=$practice_id;
            //$physician->practice->name=$practice->name;
            //$physician->practice->hospital_id= $practice->hospital_id;
            //end-drop column practice_id from table 'physicians' changes by 1254
        }

        //Action-Redesign by 1254 : 15022020

//        $categories = ActionCategories::all();
        $categories = ActionCategories::getCategoriesByPaymentType(Request::input('payment_type'));
        $categories_except_rehab = ActionCategories::getCategoriesByPaymentType(0); //This is for all categories other than rehab payment type
        $categories_for_rehab = ActionCategories::getCategoriesByPaymentType(PaymentType::REHAB); //This is for all rehab categories only.

        //$actions = DB::table("actions")->select('actions.*')->whereIn("actions.category_id", [ 1,2,3,4,5,6,7,8,9,10])->orderBy('actions.name','asc')->get();
        // $actions = Action::whereIn('hospital_id',[0,$practice->hospital_id])
        // ->orderBy('actions.name','asc')
        // ->get();

        $actions = DB::table('actions')->select('actions.*')
            ->join('action_hospitals', 'action_hospitals.action_id', '=', 'actions.id')
            ->whereIn('action_hospitals.hospital_id', [0, $practice->hospital_id])
            ->where('action_hospitals.is_active', '=', 1)
            ->distinct()
            ->get();

        $perDiemActions = Action::where('payment_type_id', '=', PaymentType::PER_DIEM)
            ->where('action_type_id', '!=', 5)
            ->orderBy('actions.sort_order', 'asc')
            ->get();

        $perDiemUncompensatedActions = Action::where('payment_type_id', '=', PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
            ->where('action_type_id', '!=', 5)
            ->orderBy('actions.sort_order', 'asc')
            ->get();

        //keep uncompesated activity checked on create page 
        foreach ($perDiemUncompensatedActions as $uncompensatedactions) {
            $uncompensatedactions->checked = true;
        }


        $hours_calculations = range(0, 24);
        unset($hours_calculations[0]);

        $data = [
            'physician' => $physician,
            'contractTypes' => self::contractTypes(),
            'paymentTypes' => self::paymentTypes(),
            /*'contractNames' => ContractName::options(Request::input('contract_type', 1)),*/
            'contractNames' => ContractName::options(Request::input('payment_type', 1)),
            // 'agreements' => self::getAgreements($physician->practice->hospital_id),
            //Physician to multiple hosptial by 1254 : added current hospital practice id
            'agreements' => Agreement::getAgreementsForHospital($practice->hospital_id),
            'agreement_pmt_frequency' => Agreement::getAgreementsPmtFrequency($practice->hospital_id),
            'users' => $users,
            'approval_manager_type' => $approval_manager_type,
            //Action-Redesign by 1254 : 15022020
            'categories' => $categories,
            'actions' => $actions,
            'per_diem_actions' => $perDiemActions,
            'per_diem_uncompensated_action' => $perDiemUncompensatedActions,
            'hours_calculations' => $hours_calculations,
            'categories_count' => count($categories),
            'categories_except_rehab' => $categories_except_rehab,
            'categories_for_rehab' => $categories_for_rehab,
            'supervisionTypes' => options(PhysicianType::all(), 'id', 'type')
        ];
        if (Request::input('payment_type') == 4) {
            $data['contractTypes'] = ContractType::psaOptions();
        }
        return $data;
    }

    public static function getRestore($id, $pid)
    {
        $physician = self::withTrashed()->where('id', '=', $id)->first();
        if (!is_physician_owner($physician->id))
            App::abort(403);

        $physician_mail = $physician->email;
        $deleted_at = $physician->deleted_at;

        if (!$physician->restore()) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.restore_error')
            ]);
        } else {
            $user = User::onlyTrashed()->where('email', '=', $physician_mail)
                ->where('group_id', '=', 6);
            $user->restore();
            $practice = Practice::where('id', '=', $pid)->onlyTrashed()->first();
            if ($practice) {
                $practice->restore();
            }
            $physician->apiContracts()->where('practice_id', '>=', $pid)->onlyTrashed()->restore();
            //$physician->logs()->restore();
            // for manually deleted logs befor soft delete physician
            PhysicianLog::onlyTrashed()->where('practice_id', '>=', $pid)->where('physician_id', '=', $id)->onlyTrashed()->update(['deleted_at' => NULL]);
            //$physician->reports()->restore();


            PhysicianPractices::where('physician_id', '=', $id)
                ->where('practice_id', '=', $pid)
                ->onlyTrashed()
                ->restore();
        }

        return Redirect::route('practices.physicians', $pid)->with([
            'success' => Lang::get('physicians.restore_success')
        ]);
    }

    public static function get_isInterfaceReady($id, $interface_type)
    {
        if ($interface_type == 1) {
            $physicianInterface = PhysicianInterfaceLawsonApcinvoice::where('physician_id', $id)->whereNull('deleted_at')->first();
            if ($physicianInterface) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function getProvidersForPerformansDashboard($user_id, $region, $facility, $practice_type, $group_id, $contract_type, $specialty_id)
    {
        $default = [0 => 'All'];

        $providers = Physician::select("physicians.id as id", DB::raw("TRIM(concat(physicians.last_name, ' ', physicians.first_name)) as name"))
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->join("practice_types", "practice_types.id", "=", "practices.practice_type_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("specialties", "specialties.id", "=", "physicians.specialty_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $providers = $providers->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $providers = $providers->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $providers = $providers->where('region_hospitals.region_id', '=', $region);
        }
        if ($facility != 0) {
            $providers = $providers->where('hospitals.id', '=', $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $providers = $providers->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $providers = $providers->whereBetween('practice_types.id', array(1, 3));
            } elseif ($practice_type == 3) {
                $providers = $providers->whereBetween('practice_types.id', array(4, 6));
            } else {
                $providers = $providers->whereBetween('practice_types.id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $providers = $providers->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty_id != 0) {
            $providers = $providers->where("physicians.specialty_id", "=", $specialty_id);
        }

        $providers = $providers->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("physicians.deleted_at")
            ->whereNotIn('contracts.payment_type_id', array(3, 5, 8))
            ->orderBy('name', 'asc')
            ->distinct()
            ->pluck('name', 'id')->toArray();

        return $default + $providers;
    }

    public static function getManagementProviderChart($user_id, $region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty)
    {
        $return_data = array();

        // $contracts = Contract::select('contracts.*', 'physician_contracts.physician_id as physician_ids')
        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        if ($provider != 0) {
            $contracts = $contracts->where("physicians.id", "=", $provider);
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $total_durations = PhysicianLog::
            select(DB::raw("action_categories.id as category_id, action_categories.name as category_name, SUM(physician_logs.duration) as total_duration"))
                ->join("actions", "actions.id", "=", "physician_logs.action_id")
                ->join("action_categories", "action_categories.id", "=", "actions.category_id");

            if ($provider != 0) {
                $total_durations = $total_durations->where("physician_logs.physician_id", "=", $provider);
            }

            $total_durations = $total_durations->where("physician_logs.contract_id", "=", $contract->id)
                ->where('physician_logs.physician_id', '=', $contract->physician_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull("physician_logs.deleted_at")
                // ->groupBy('action_categories.id')
                ->orderBy('action_categories.id', 'asc')
                ->distinct()->get();

            if (count($total_durations) > 0) {
                foreach ($total_durations as $logs_duration) {
                    if ($logs_duration->category_id) {
                        $collection = collect($return_data);
                        $check_exist = $collection->contains('category_id', $logs_duration->category_id);

                        if ($check_exist) {
                            $data = collect($return_data)->where('category_id', $logs_duration->category_id)->all();
                            foreach ($data as $data) {
                                $total_duration = $data["total_duration"] + $logs_duration->total_duration;

                                $category_id = $data["category_id"];

                                foreach ($return_data as $key => $value) {
                                    if ($value["category_id"] == $category_id) {
                                        unset($return_data[$key]);
                                    }
                                }

                                $return_data[] = [
                                    "category_id" => $logs_duration->category_id,
                                    "category_name" => $logs_duration->category_name,
                                    "total_duration" => formatNumber($total_duration) . ""
                                ];
                            }
                        } else {
                            $return_data[] = [
                                "category_id" => $logs_duration->category_id,
                                "category_name" => $logs_duration->category_name,
                                "total_duration" => formatNumber($logs_duration->total_duration) . ""
                            ];
                        }
                    }
                }
            }
        }
        $data = collect($return_data)->sortBy('category_id')->toArray();
        return $data;
    }

    public static function getActualToExpectedTimeProviderChart($user_id, $region, $facility, $practice_type, $provider, $group_id, $contract_type, $specialty)
    {
        $return_data = array();

        // $contracts = Contract::select('contracts.*', 'physician_contracts.physician_id as physician_ids')
        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'contracts.expected_hours', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        if ($provider != 0) {
            $contracts = $contracts->where("physicians.id", "=", $provider);
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        $total_expected_hours = 0;
        $total_actual_hours = 0;
        $total_remaining_hours = 0;
        $contract_ids = [];

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $expected_hours = $contract->expected_hours;

            $start_date = $startEndDatesForYear['year_start_date']->format('Y-m-d');
            $end_date = date("Y-n-j", strtotime("last day of previous month"));

            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);

            $year_start_date = date('Y', $start_date);
            $year_end_date = date('Y', $end_date);

            $month_start_date = date('m', $start_date);
            $month_end_date = date('m', $end_date);

            $months_diff = (($year_end_date - $year_start_date) * 12) + ($month_end_date - $month_start_date);
            $prior_periods = $months_diff + 1;

            $total_expected_hours = $prior_periods * $expected_hours;

            $total_durations = PhysicianLog::select(DB::raw("SUM(physician_logs.duration) as total_durations"))
                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                ->whereNull("physician_logs.deleted_at")
                ->where('physician_logs.contract_id', '=', $contract->id)
                ->where('physician_logs.physician_id', '=', $contract->physician_id);

            // if($provider != 0){
            //     $total_durations = $total_durations->where("physician_logs.physician_id", "=", $provider);
            // }

            $total_durations = $total_durations->where("log_approval.approval_managers_level", ">", 0)
                ->where("log_approval.approval_status", "=", "1")
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->first();

            if ($total_durations->total_durations) {
                $total_durations = $total_durations->total_durations;
                $actual_hours = $total_durations;
                $remaining_hours = $total_durations > $total_expected_hours ? 0 : $total_expected_hours - $total_durations;

                $total_actual_hours += $actual_hours;
                if (!in_array($contract->id, $contract_ids)) {
                    array_push($contract_ids, $contract->id);
                    $total_remaining_hours += $remaining_hours;
                }
            }
        }

        if (count($contracts) > 0) {
            if ($total_actual_hours > 0 || $total_remaining_hours > 0) {
                $return_data[] = [
                    "type_id" => '1001',
                    "type_name" => 'Actual Hours',
                    "total_hours" => formatNumber($total_actual_hours) . ""
                ];

                $return_data[] = [
                    "type_id" => '1002',
                    "type_name" => 'Remaining Expected',
                    "total_hours" => formatNumber($total_remaining_hours) . ""
                ];
            }
        }

        return $return_data;
    }

    public static function getHospitalPhysiciansDataForComplianceReports($user_id, $facility, $contract_type_id)
    {
        $query = self::select("physicians.*")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")

            // ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            // ->join("hospitals", "hospitals.id", "=", "physician_practices.hospital_id")
            // ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            // ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            // ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("physicians.deleted_at");

        if ($facility != 0) {
            $query = $query->where('hospitals.id', '=', $facility);
        }

        if ($contract_type_id != -1) {
            $query = $query->where('contract_types.id', '=', $contract_type_id);
        }

        $query = $query->distinct()->get();
        return $query;
    }

    public static function getHospitalApproversDataForComplianceReports($user_id, $facility, $contract_type)
    {
        $query = User::select("users.*")
            ->join("hospital_user", "users.id", "=", "hospital_user.user_id")
            ->join("agreement_approval_managers_info", "agreement_approval_managers_info.user_id", "=", "hospital_user.user_id")
            ->join('agreements', 'agreements.id', '=', 'agreement_approval_managers_info.agreement_id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->whereNull("users.deleted_at");

        if ($facility != 0) {
            $query = $query->where('hospital_user.hospital_id', '=', $facility);
        }

        if ($contract_type != -1) {
            $query = $query->where('contracts.contract_type_id', '=', $contract_type);
        }

        $query = $query
            ->whereNull("agreements.deleted_at")
            ->where("agreements.is_deleted", "=", 0)
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        $query = $query->distinct()->get();

        return $query;
    }

    public static function getPhysicianDataForHealthSystemReports($hospitals, $agreements, $start_date, $end_date)
    {
        if ($agreements == null) {
            return [];
        }

        $query = self::select(
            DB::raw("physicians.id as physician_id"),
            DB::raw("physicians.first_name as first_name"),
            DB::raw("physicians.last_name as last_name")
        )
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            // ->join("contracts", "contracts.physician_id", "=", "physicians.id")  // 6.1.1.12
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("practices", "practices.id", "=", "physician_practices.practice_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->whereIn("contracts.agreement_id", $agreements)
            ->whereNull("contracts.deleted_at")
            ->whereIn("physician_practices.hospital_id", $hospitals)
            ->orderBy("physicians.last_name", "asc")
            ->orderBy("physicians.first_name", "asc");

        //physician to multiple hospital by 1254
        $results = $query->distinct()->get();

        $data = [];
        foreach ($results as $result) {
            $currentPractices = DB::table("practices")->select(
                DB::raw("practices.id"),
                DB::raw("practices.name")
            )
                ->join("physician_practices", "practices.id", "=", "physician_practices.practice_id")
                ->where("physician_practices.physician_id", $result->physician_id)
                ->whereIn("physician_practices.hospital_id", $hospitals)
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
                        $start = $start_date[$contract->agreement_id];
                        $end = $end_date[$contract->agreement_id];
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
                        $contractDateEnd = date('Y-m-d', strtotime($contractEndDate[0]));
                        //echo $paymentDate; // echos today!
                        $reportDateBegin = date('Y-m-d', strtotime($start));
                        $reportDateEnd = date('Y-m-d', strtotime($end));

                        if (($contractDateEnd > $reportDateBegin) && ($contractDateEnd < $reportDateEnd)) {
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

    /**
     * @param $hospital_id
     * @return array|void
     * Below function will be used for getting all the physicians available in hospitals.
     */
    public static function getAllPhysiciansForHospital($hospital_id)
    {
        if ($hospital_id) {
            return $hospitals_physicians = DB::table('physicians')
                ->select('physicians.id', DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), 'practices.id as practice_id', 'practices.name as practice_name')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                ->where('practices.hospital_id', '=', $hospital_id)
                ->whereNull('physicians.deleted_at')
				->whereNull('practices.deleted_at')
                ->where('physician_practices.start_date', '<=', now())
                ->where('physician_practices.end_date', '>=', now())
                ->groupBy('physicians.id')
                ->groupBy('practices.id')
                ->get();
        } else {
            return [];
        }
    }

    public function hospitals()
    {
        return $this->hasMany('App\PhysicianPractices', 'physician_id');
    }
}
