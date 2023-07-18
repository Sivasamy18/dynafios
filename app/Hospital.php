<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Controllers\Validations\HospitalValidation;
use OwenIt\Auditing\Contracts\Auditable;
use Request;
use Redirect;
use Lang;
use DateTime;
use Log;
use function App\Start\is_approval_manager;
use function App\Start\is_health_system_region_user;
use function App\Start\is_health_system_user;
use function App\Start\is_hospital_admin;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;

class Hospital extends Model implements Auditable
{

    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'hospitals';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function createHospital()
    {
        $validation = new HospitalValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $hospital = new Hospital();
        $hospital->name = Request::input('name');
        $hospital->npi = Request::input('npi');
        $hospital->address = Request::input('address');
        $hospital->city = Request::input('city');
        $hospital->state_id = Request::input('state');
        $hospital->benchmark_rejection_percentage = Request::input('benchmark') != '' ? floatval(Request::input('benchmark')) : 0.00;
        $hospital->expiration = mysql_date(Request::input('expiration'));
        $hospital->facility_type = Request::input('facility_type');


        $npiFacilityType =  DB::table('hospitals')->where("npi","=",$hospital->npi)->where('facility_type', $hospital->facility_type)->first();

        if( $npiFacilityType ==null){
            if (!$hospital->save()) {
                return Redirect::back()
                    ->with(['error' => Lang::get('hospitals.create_error')])
                    ->withInput();
            }
            if (Request::input('note_count') > 0) {
                $index = 1;
                for ($i = 1; $i <= Request::input('note_count'); $i++) {
                    if (Request::input("note" . $i) != '') {
                        $invoice_note = new InvoiceNote();
                        $invoice_note->note_type = InvoiceNote::HOSPITAL;
                        $invoice_note->note_for = $hospital->id;
                        $invoice_note->note_index = $index;
                        $invoice_note->note = Request::input("note" . $i);
                        $invoice_note->is_active = true;
                        $invoice_note->hospital_id = $hospital->id;
                        $invoice_note->save();
                        $index++;
                    }
                }
            }

        }else{
            return Redirect::back()
                ->with(['error' => Lang::get('hospitals.npiFacilityType_error')])
                ->withInput();
        }


        return Redirect::route('hospitals.index')->with([
            'success' => Lang::get('hospitals.create_success')
        ]);
    }

    public static function editHospital($id)
    {
        $hospital = Hospital::findOrFail($id);
        $current_password_expiration = $hospital->password_expiration_months;

        //if (!is_super_user())
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new HospitalValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $hospital->name = Request::input('name');
        $hospital->npi = Request::input('npi');
        $hospital->address = Request::input('address');
        $hospital->city = Request::input('city');
        $hospital->state_id = Request::input('state');
        $hospital->expiration = mysql_date(Request::input('expiration'));
        $hospital->password_expiration_months = Request::input('password_expiration_months');
        $hospital->assertation_text = Request::input('assertation_text');
        $hospital->benchmark_rejection_percentage = Request::input('benchmark') != '' ? floatval(Request::input('benchmark')) : 0.00;
        $hospital->invoice_dashboard_on_off = Request::input("invoice_dashboard_on_off");
        $hospital->approve_all_invoices = Request::input("approve_all_invoices");
        $hospital->facility_type = Request::input('facility_type');

        if (!$hospital->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('hospitals.edit_error')])
                ->withInput();
        } else {
            // Clear the current primary hospital user.
            DB::table("hospital_user")
                ->where("hospital_id", "=", $hospital->id)
                ->update(["primary" => false]);

            // Set the new primary hospital user.
            DB::table("hospital_user")
                ->where("hospital_id", "=", $hospital->id)
                ->where("user_id", "=", Request::input("primary_user_id"))
                ->update(["primary" => true]);

            if ($current_password_expiration != $hospital->password_expiration_months) {
                self::update_user_password_expiration_date($hospital->id, $hospital->password_expiration_months);
            }
            //call hospital routine to update user password_expiration_date

            /*update hospital notes*/
            $index = 1;
            if (Request::input('note_count') > 0) {
                for ($i = 1; $i <= Request::input('note_count'); $i++) {
                    if (Request::input("note" . $i) != '') {
                        $invoice_note_old = InvoiceNote::where("note_type", '=', InvoiceNote::HOSPITAL)
                            ->where("note_for", '=', $hospital->id)
                            ->where("note_index", '=', $index)
                            ->where("is_active", '=', true)
                            ->where("hospital_id", '=', $hospital->id)
                            ->update(["note" => Request::input("note" . $i)]);
                        if (!$invoice_note_old) {
                            $invoice_note = new InvoiceNote();
                            $invoice_note->note_type = InvoiceNote::HOSPITAL;
                            $invoice_note->note_for = $hospital->id;
                            $invoice_note->note_index = $index;
                            $invoice_note->note = Request::input("note" . $i);
                            $invoice_note->is_active = true;
                            $invoice_note->hospital_id = $hospital->id;
                            $invoice_note->save();
                        }
                        $index++;
                    }
                }
            }
            InvoiceNote::where("note_type", '=', InvoiceNote::HOSPITAL)
                ->where("note_for", '=', $hospital->id)
                ->where("note_index", '>=', $index)
                ->where("is_active", '=', true)
                ->where("hospital_id", '=', $hospital->id)
                ->update(["is_active" => false]);
            $hospital_feature_details = new   HospitalFeatureDetails();
            $hospital_feature_details->hospital_id = $hospital->id;
            $hospital_feature_details->performance_on_off = Request::input("performance_on_off") != '' ? Request::input("performance_on_off") : 0;
            $hospital_feature_details->compliance_on_off = Request::input("compliance_on_off") != '' ? Request::input("compliance_on_off") : 0;
            $hospital_feature_details->created_by = Auth::user()->id;
            $hospital_feature_details->updated_by = Auth::user()->id;
            $hospital_feature_details->save();
        }

        return Redirect::route('hospitals.edit', $hospital->id)
            ->with(['success' => Lang::get('hospitals.edit_success')]);
    }

    public static function update_user_password_expiration_date($hospital_id, $password_expiration_months = 99)
    {
        $date = new DateTime("+" . $password_expiration_months . " months");
        $today = date("Y-m-d");
        $users = DB::table("hospital_user")
            ->select("users.id as id")
            ->join("users", "users.id", "=", "hospital_user.user_id")
            ->where("hospital_user.hospital_id", "=", $hospital_id)
            ->where("users.password_expiration_date", ">", $today)
            //->whereRaw("hospital_user.hospital >= now()")
            ->distinct()
            ->pluck('id');
        //Update users table records
        if ($users) {
            DB::table('users')
                ->whereIn('id', $users)
                ->update(array('password_expiration_date' => $date));
        }

        $practice_users = DB::table("practice_user")
            ->select("users.id as id")
            ->join("practices", "practices.id", "=", "practice_user.practice_id")
            ->join("users", "users.id", "=", "practice_user.user_id")
            ->where("practices.hospital_id", "=", $hospital_id)
            ->where("users.password_expiration_date", ">", $today)
            //->whereRaw("hospital_user.hospital >= now()")
            ->distinct()
            ->pluck('id');
        //Update users table records
        if ($practice_users) {
            DB::table('users')
                ->whereIn('id', $practice_users)
                ->update(array('password_expiration_date' => $date));
        }

        $physician_users = DB::table("practices")
            ->select("users.id as id")
            //drop column practice_id from table 'physicians' changes by 1254
            // ->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            ->join("users", "users.email", "=", "physicians.email")
            ->where("practices.hospital_id", "=", $hospital_id)
            ->where("users.group_id", "=", Group::Physicians)
            ->where("users.password_expiration_date", ">", $today)
            //->whereRaw("hospital_user.hospital >= now()")
            ->distinct()
            ->pluck('id');
        if ($physician_users) {
            DB::table('users')
                ->whereIn('id', $physician_users)
                ->update(array('password_expiration_date' => $date));
        }
    }

    public static function fetch_contract_stats()
    {
        /*->whereRaw("agreements.is_deleted=0") is added for soft delete agreement on 12/04/2016*/
        $query = DB::table("contract_types")
            ->select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $return_data = array();
        foreach ($contract_types as $contract_type) {

            $hospital_count = DB::table("agreements")
                ->select(DB::raw("distinct(agreements.hospital_id)"))
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived = 0")
                ->where("contracts.contract_type_id", "=", $contract_type->id)
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('hospitals.deleted_at');
            $hospital_counts = $hospital_count->get();

            $physician_count = DB::table("contracts")
                ->select(DB::raw("distinct(contracts.physician_id)"))
                ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("contracts.contract_type_id", "=", $contract_type->id)
                ->whereRaw("hospitals.archived = 0")
                ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where("agreements.hospital_id", "<>", 113)
                ->whereNull('physicians.deleted_at');
            $physician_counts = $physician_count->get();

            $hospital_user_count = DB::table("hospital_user")
                ->select(DB::raw("distinct(hospital_user.user_id)"))
                ->join("users", "users.id", "=", "hospital_user.user_id")
                ->join("agreements", "agreements.hospital_id", "=", "hospital_user.hospital_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived = 0")
                ->where("agreements.hospital_id", "<>", 113)
                ->where("contracts.contract_type_id", "=", $contract_type->id)
                ->whereNull('users.deleted_at');
            $hospital_user_counts = $hospital_user_count->get();

            $practice_user_count = DB::table("practice_user")
                ->select(DB::raw("distinct(practice_user.user_id)"))
                ->join("users", "users.id", "=", "practice_user.user_id")
                ->join("practices", "practices.id", "=", "practice_user.practice_id")
                ->join("agreements", "agreements.hospital_id", "=", "practices.hospital_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->whereRaw("hospitals.archived = 0")
                ->where("agreements.hospital_id", "<>", 113)
                ->where("contracts.contract_type_id", "=", $contract_type->id)
                ->whereNull('users.deleted_at');
            $practice_user_counts = $practice_user_count->get();

            if (count($hospital_counts) > 0 || count($physician_counts) > 0
                || count($hospital_user_counts) > 0 || count($practice_user_counts) > 0
            ) {
                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "hospital_counts" => count($hospital_counts),
                    "physician_counts" => count($physician_counts),
                    "hospital_user_counts" => count($hospital_user_counts),
                    "practice_user_counts" => count($practice_user_counts)
                ];
            }
        }
        return $return_data;
    }

    public static function fetch_contract_stats_using_union()
    {
        /*->whereRaw("agreements.is_deleted=0") is added for soft delete agreement on 12/04/2016*/
        $queryunion = DB::select(DB::raw("select `contracts`.`contract_type_id` as contract_type_id, contract_types.name, count(distinct agreements.hospital_id)  as count, 'hospital_counts' as type from `agreements` inner join `hospitals` on `hospitals`.`id` = `agreements`.`hospital_id` inner join `contracts` on `contracts`.`agreement_id` = `agreements`.`id` inner join contract_types on contract_types.id = `contracts`.`contract_type_id` where agreements.is_deleted=0 and agreements.start_date <= now() and agreements.end_date >= now() and hospitals.archived = 0 and `agreements`.`hospital_id` <> 113 and `hospitals`.`deleted_at` is null group by contract_type_id
                                union
                                select `contracts`.`contract_type_id` as contract_type_id, contract_types.name, count(distinct contracts.physician_id) as count, 'physician_counts' as type from `contracts` inner join contract_types on contract_types.id = `contracts`.`contract_type_id` inner join `physicians` on `physicians`.`id` = `contracts`.`physician_id` inner join `agreements` on `agreements`.`id` = `contracts`.`agreement_id` inner join `hospitals` on `hospitals`.`id` = `agreements`.`hospital_id` where agreements.is_deleted=0 and agreements.start_date <= now() and agreements.end_date >= now() and hospitals.archived = 0 and contracts.manual_contract_end_date >= now() and `agreements`.`hospital_id` <> 113 and `physicians`.`deleted_at` is null group by contract_type_id
                                union
                                select `contracts`.`contract_type_id` as contract_type_id, contract_types.name, count(distinct hospital_user.user_id) as count, 'hospital_user_counts' as type from `hospital_user` inner join `users` on `users`.`id` = `hospital_user`.`user_id` inner join `agreements` on `agreements`.`hospital_id` = `hospital_user`.`hospital_id` inner join `hospitals` on `hospitals`.`id` = `agreements`.`hospital_id` inner join `contracts` on `contracts`.`agreement_id` = `agreements`.`id` inner join contract_types on contract_types.id = `contracts`.`contract_type_id` where agreements.is_deleted=0 and agreements.start_date <= now() and agreements.end_date >= now() and hospitals.archived = 0 and `agreements`.`hospital_id` <> 113 and `users`.`deleted_at` is null group by contract_type_id
                                union
                                select  `contracts`.`contract_type_id` as contract_type_id, contract_types.name, count(distinct practice_user.user_id)  as count, 'practice_user_counts' as type from `practice_user` inner join `users` on `users`.`id` = `practice_user`.`user_id` inner join `practices` on `practices`.`id` = `practice_user`.`practice_id` inner join `agreements` on `agreements`.`hospital_id` = `practices`.`hospital_id` inner join `hospitals` on `hospitals`.`id` = `agreements`.`hospital_id` inner join `contracts` on `contracts`.`agreement_id` = `agreements`.`id` inner join contract_types on contract_types.id = `contracts`.`contract_type_id` where agreements.is_deleted=0 and agreements.start_date <= now() and agreements.end_date >= now() and hospitals.archived = 0 and `agreements`.`hospital_id` <> 113 and `users`.`deleted_at` is null group by contract_type_id"));
        //Log::info('queryunion ',array($queryunion));

        $return_data = array();
        foreach ($queryunion as $counts) {
            if (!array_key_exists($counts->contract_type_id, $return_data)) {
                $return_data[$counts->contract_type_id] = [
                    "contract_type_id" => $counts->contract_type_id,
                    "contract_type_name" => $counts->name,
                    "hospital_counts" => 0,
                    "physician_counts" => 0,
                    "hospital_user_counts" => 0,
                    "practice_user_counts" => 0
                ];
            }
            $return_data[$counts->contract_type_id][$counts->type] = $counts->count;
        }
        //Log::info("return_data", array($return_data));
        return $return_data;
    }

    public static function fetch_contract_stats_for_hospital_users($user_id, $manager_type = 0)
    {
        $hospital_ids = self::select('hospitals.id')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->get();

        /*->whereRaw("agreements.is_deleted=0") is added for soft delete agreement on 12/04/2016*/
        $query = DB::table("contract_types")
            ->select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contract_count = array();
            if ((is_approval_manager() && !(is_super_hospital_user())) && $manager_type != -1) {
                $contracts = DB::table("contracts")
                    ->select("contracts.*")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                    ->whereRaw("agreements.is_deleted='0'")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.contract_type_id", "=", $contract_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                    ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->where('agreement_approval_managers_info.is_deleted', '=', '0')
                    ->whereNull('contracts.deleted_at')->distinct()->get();

                foreach ($contracts as $contract) {
                    $contract_present = 0;
                    // add new approval check 30Aug2018
                    if ($contract->default_to_agreement == 0) {
                        //Check contract level CM FM
                        $agreement_approval_managers_info = ApprovalManagerInfo::where("contract_id", "=", $contract->id)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)->first();
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->first();

                        if ($agreement_approval_managers_info != null) {
                            $contract_present = 1;
                        } else {
                            $contract_present = 0;
                        }
                    } else {
                        //Check contract level CM FM
                        $agreement_approval_managers_info = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                            ->where("contract_id", "=", 0)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)->first();
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->first();

                        if ($agreement_approval_managers_info != null) {
                            $contract_present = 1;
                        } else {
                            $contract_present = 0;
                        }
                    }
                    if ($contract_present == 1) {
                        $contract_count[] = $contract;
                    }
                }
            } elseif (is_super_hospital_user() || is_hospital_admin()) {
                $contract_count = DB::table("contracts")
                    ->select(DB::raw("contracts.*"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.contract_type_id", "=", $contract_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where("hospital_user.user_id", "=", $user_id)
                    ->whereIn('hospital_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->whereNull('contracts.deleted_at')->distinct()->get();
            } elseif (is_practice_manager()) {
                $contract_count = DB::table("contracts")
                    ->select(DB::raw("contracts.*"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                    ->join("practice_user", "practice_user.practice_id", "=", "practices.id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.contract_type_id", "=", $contract_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where("practice_user.user_id", "=", $user_id)
                    ->whereIn('practice_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->whereNull('contracts.deleted_at')->distinct()->get();
            }
            $contract_count = $contract_count;

            if (count($contract_count) > 0) {
                $results = self::getHospitalsContractTotalSpendAndPaid($hospital_ids->toArray(), $contract_type->id);

                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "active_contract_count" => count($contract_count),
                    "total_spend" => $results['contract_spend'],
                    "total_paid" => $results['contract_paid']
                ];
            }

            // $contract_spend = Hospital::totalSpend($contract_count,$contract_type);
            // $contract_paid = Hospital::totalPaid($contract_count);

            // if (count($contract_count) > 0 ) {
            //     $return_data[] = [
            //         "contract_type_id" => $contract_type->id,
            //         "contract_type_name" => $contract_type->name,
            //         "active_contract_count"=>count($contract_count),
            //         "total_spend" => $contract_spend,
            //         "total_paid" => $contract_paid
            //     ];
            // }
        }
        return $return_data;
    }

    public static function getHospitalsContractTotalSpendAndPaid($hospital_ids, $contract_type_id)
    {
        $results = HospitalContractSpendPaid::select(DB::raw("SUM(contract_spend) as contract_spend"),
            DB::raw("SUM(contract_paid) as contract_paid"),
            DB::raw("SUM(active_contract_count) as active_contract_count"))
            ->whereIn('hospital_id', $hospital_ids)
            ->where('contract_type_id', '=', $contract_type_id)
            ->first();

        if ($results['contract_spend'] == null) {
            $results['contract_spend'] = 0;
        }
        if ($results['contract_paid'] == null) {
            $results['contract_paid'] = 0;
        }
        if ($results['active_contract_count'] == null) {
            $results['active_contract_count'] = 0;
        }

        return $results;
    }

    public static function fetch_contract_stats_for_hospital_users_performance_dashboard($user_id)
    {
        $query = ContractType::
        select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();
        // Log::info("contract_types hospital.php",array($contract_types));

        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contract_count = array();

            $contracts = Contract::
            select("contracts.*")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereRaw("agreements.is_deleted='0'")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("contracts.contract_type_id", "=", $contract_type->id)
                ->where("agreements.hospital_id", "<>", 45)
                ->whereNull('contracts.deleted_at')->distinct()->get();
            if (count($contracts) > 0) {
                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "active_contract_count" => count($contract_count),

                ];
            }
        }
        //  Log::info("contract type return_data hospital.php",array($return_data));
        return $return_data;
    }

    public static function fetch_contract_names_for_hospital_users($user_id, $hospital_id, $payment_ids, $contract_type)
    {
        $query = DB::table("contract_names")
            ->select(DB::raw("distinct(contract_names.id),contract_names.name,contract_names.contract_type_id,contract_names.payment_type_id"))
            ->join("contracts", "contracts.contract_name_id", "=", "contract_names.id")
            ->join("payment_types", "payment_types.id", "=", "contracts.payment_type_id")
            ->whereIn('contract_names.payment_type_id', array_keys($payment_ids));

        $contract_names = $query->get();


        $return_data = array();
        foreach ($contract_names as $contract_name) {

            if (count($contract_names) > 0) {
                $return_data[] = [
                    "contract_name_id" => $contract_name->id,
                    "contract_type_id" => $contract_name->contract_type_id,
                    "contract_name" => $contract_name->name,
                    "payment_type_id" => $contract_name->payment_type_id,

                ];
            }
        }

        return $return_data;
    }

    public static function agreements_for_hospital_users($user_id)
    {
        $contracts_data = array();
        $hospitals_data = array();
        $region_ids = array();
        $hospital_ids = array();
        $agreement_ids = array();
        $region_hospital_ids = array();
        $hospital_agreement_ids = array();
        $region_id = 0;
        $hospital_id = 0;
        $today = date('Y-m-d');
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        if (is_approval_manager() && !(is_super_hospital_user())) {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name")
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted=0")
                // ->whereRaw("agreements.start_date <= now()")
                // ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.start_date", '<=', mysql_date($today))
                ->where("agreements.end_date", '>=', mysql_date($today))
                ->where("agreements.hospital_id", "<>", 45)
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->whereIn("agreement_approval_managers_info.user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();

        } elseif (is_health_system_user()) {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name", "health_system_regions.id as region_id", "health_system_regions.region_name as region_name")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id")
                ->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->whereRaw("agreements.is_deleted=0")
                // ->whereRaw("agreements.start_date <= now()")
                // ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.start_date", '<=', mysql_date($today))
                ->where("agreements.end_date", '>=', mysql_date($today))
                ->where("agreements.hospital_id", "<>", 45)
                ->where("health_system_users.user_id", "=", $user_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('health_system_regions.deleted_at')
                ->whereNull('health_system_users.deleted_at')
                ->orderBy("health_system_regions.id")
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();
        } elseif (is_health_system_region_user()) {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->whereRaw("agreements.is_deleted=0")
                // ->whereRaw("agreements.start_date <= now()")
                // ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.start_date", '<=', mysql_date($today))
                ->where("agreements.end_date", '>=', mysql_date($today))
                ->where("agreements.hospital_id", "<>", 45)
                ->where("health_system_region_users.user_id", "=", $user_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('health_system_region_users.deleted_at')
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();
        } else {
            $allAgreements = DB::table("agreements")
                ->select("agreements.*", "hospitals.name as hospital_name")
                ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted=0")
                // ->whereRaw("agreements.start_date <= now()")
                // ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.start_date", '<=', mysql_date($today))
                ->where("agreements.end_date", '>=', mysql_date($today))
                ->where("agreements.hospital_id", "<>", 45)
                //->where("hospital_user.user_id", "=", $user_id)
                ->whereIn('hospital_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                ->orderBy("agreements.hospital_id")
                ->orderBy("agreements.id")
                ->distinct()->get();
        }
        foreach ($allAgreements as $agreement) {
            if (is_health_system_user()) {
                if ($region_id != $agreement->region_id) {
                    $region_ids[] = ["region_id" => $agreement->region_id,
                        "region_name" => $agreement->region_name];
                    if ($region_id != 0) {
                        $region_hospital_ids[$region_id] = $hospital_ids;
                        /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                        unset($hospital_ids);
                        $hospital_ids = array();
                    }
                    $region_id = $agreement->region_id;
                }
                if ($hospital_id != $agreement->hospital_id) {
                    $hospital_ids[] = ["hospital_id" => $agreement->hospital_id,
                        "hospital_name" => $agreement->hospital_name];
                    if ($hospital_id != 0) {
                        $hospital_agreement_ids[$hospital_id] = $agreement_ids;
                        /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                        unset($agreement_ids);
                        $agreement_ids = array();
                    }
                    $hospital_id = $agreement->hospital_id;
                }
                $agreement_ids[] = $agreement->id;
            } else {
                if ($hospital_id != $agreement->hospital_id) {
                    $hospital_ids[] = ["hospital_id" => $agreement->hospital_id,
                        "hospital_name" => $agreement->hospital_name];
                    if ($hospital_id != 0) {
                        $hospital_agreement_ids[$hospital_id] = $agreement_ids;
                        /*added to solve the users dashboard multiple hospitals contracts merge issue*/
                        unset($agreement_ids);
                        $agreement_ids = array();
                    }
                    $hospital_id = $agreement->hospital_id;
                }
                $agreement_ids[] = $agreement->id;
            }
        }
        if (is_health_system_user()) {
            $region_hospital_ids[$region_id] = $hospital_ids;
        }
        $hospital_agreement_ids[$hospital_id] = $agreement_ids;
        if (is_health_system_user()) {
            foreach ($region_ids as $region) {
                foreach ($region_hospital_ids[$region["region_id"]] as $hospital) {
//                    $practice_info = Contract::get_agreements_contract_info($hospital_agreement_ids[$hospital["hospital_id"]]);
                    $practice_info = [];
                    $hospitals_data[] = [
                        'hospital_id' => $hospital["hospital_id"],
                        'hospital_name' => $hospital["hospital_name"],
                        'contracts_info' => $practice_info
                    ];
                }
                $contracts_data[] = [
                    'region_name' => $region["region_name"],
                    'hospitals_info' => $hospitals_data
                ];
                unset($hospitals_data);
                $hospitals_data = array();
            }
        } else {
            foreach ($hospital_ids as $hospital) {
                if (is_health_system_region_user()) {
                    $practice_info = [];
                } else {
                    $practice_info = Contract::get_agreements_contract_info($hospital_agreement_ids[$hospital["hospital_id"]]);
                }
                $contracts_data[] = [
                    'hospital_id' => $hospital["hospital_id"],
                    'hospital_name' => $hospital["hospital_name"],
                    'contracts_info' => $practice_info
                ];
            }
        }
        return $contracts_data;
    }

    public static function getHospitals($user_id)
    {

        //$default=["0"=>"All"];
        //$hospitals = self::select('hospitals.id as id', 'hospitals.name as name')

        $hospital = self::select('hospitals.id as id', 'hospitals.name as name')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('hospitals.archived', '=', false)
            ->where('hospital_user.user_id', '=', $user_id)
            ->orderBy('hospitals.name')
            ->distinct()
            ->pluck("name", "id");

        // $hospital = array();
        /*     $hospital_list=array();
             foreach($hospitals as $hospital_id=> $hospital_name)
             {
                 $performance_on_off =  DB::table('hospital_feature_details')->where("hospital_id","=",$hospital_id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                 if($performance_on_off == 1)
                 {
                     $hospital[$hospital_id] = $hospital_name;
                     $hospital_list=$hospital;

                 }
             }*/
        $hospital_list = $hospital;
        return $hospital_list->toArray();
    }

    public static function getApprovalUserHospitals($user_id, $manager_type)
    {
        //$manager_type=$managers['type_id'];
        //Log::info($managers);
        $default = ["0" => "All"];
        //$LogApproval=new LogApproval();

        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        if ($manager_type == -1 && (is_super_hospital_user() || is_hospital_admin())) {
            $hospital = self::select('hospitals.id as id', 'hospitals.name as name')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospitals.archived', '=', false)
                //->where('hospital_user.user_id', '=', $user_id)
                ->whereIn('hospital_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                ->orderBy('hospitals.name')
                ->distinct()
                ->pluck("name", "id");
        } else if ($manager_type == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $practice_ids = array();
            foreach (Auth::user()->practices as $practice) {
                $practice_ids[] = $practice->id;
            }
            if (count($practice_ids) == 0) {
                $practice_ids[] = 0;
            }
            $hospital = self::select('hospitals.id as id', 'hospitals.name as name')
                ->join('practices', 'practices.hospital_id', '=', 'hospitals.id')
                ->where('hospitals.archived', '=', false)
                ->whereIn('practices.id', $practice_ids)
                ->orderBy('hospitals.name')
                ->distinct()
                ->pluck("name", "id");
        } else {
            $hospital = self::select('hospitals.id as id', 'hospitals.name as name')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->join('agreements', 'agreements.hospital_id', '=', 'hospitals.id')
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->orderBy('hospitals.name')
                ->distinct()
                ->pluck("name", "id");
        }

        $hospital_list = $hospital;

        return $default + $hospital_list->toArray();
    }

    public static function fetch_payment_stats_for_hospital_users($user_id, $manager_type = 0)
    {
        /*->whereRaw("agreements.is_deleted=0") is added for soft delete agreement on 12/04/2016 */
        $query = DB::table("payment_types")
            ->select(DB::raw("distinct(payment_types.id),payment_types.name"))
            ->join("contracts", "contracts.payment_type_id", "=", "payment_types.id");
        $payment_types = $query->get();
        //log::info('types',array($query));
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        $return_data = array();
        foreach ($payment_types as $payment_type) {
            $contract_count = array();
            if (is_approval_manager() && $manager_type != -1) {
                $contracts = DB::table("contracts")
                    ->select("contracts.*")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.payment_type_id", "=", $payment_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                    ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->where('agreement_approval_managers_info.is_deleted', '=', '0')->distinct()->get();
                foreach ($contracts as $contract) {
                    $contract_present = 0;
                    // add new approval check 30Aug2018
                    if ($contract->default_to_agreement == 0) {
                        //Check contract level CM FM
                        $agreement_approval_managers_info = ApprovalManagerInfo::where("contract_id", "=", $contract->id)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)->first();
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->first();
                        if ($agreement_approval_managers_info != null) {
                            $contract_present = 1;
                        } else {
                            $contract_present = 0;
                        }
                    } else {
                        //Check contract level CM FM
                        $agreement_approval_managers_info = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                            ->where("contract_id", "=", 0)
                            ->where("is_deleted", "=", '0')
                            //->where("user_id", "=", $user_id)->first();
                            ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->first();
                        if ($agreement_approval_managers_info != null) {
                            $contract_present = 1;
                        } else {
                            $contract_present = 0;
                        }
                    }
                    if ($contract_present == 1) {
                        $contract_count[] = $contract;
                    }
                }
            } elseif (is_super_hospital_user() || is_hospital_admin()) {
                $contract_count = DB::table("contracts")
                    ->select(DB::raw("contracts.*"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.payment_type_id", "=", $payment_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where("hospital_user.user_id", "=", $user_id)
                    ->whereIn('hospital_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->distinct()->get();
            } elseif (is_practice_manager()) {
                $contract_count = DB::table("contracts")
                    ->select(DB::raw("contracts.*"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                    ->join("practice_user", "practice_user.practice_id", "=", "practices.id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.payment_type_id", "=", $payment_type->id)
                    ->where("agreements.hospital_id", "<>", 45)
                    //->where("practice_user.user_id", "=", $user_id)
                    ->whereIn('practice_user.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                    ->distinct()->get();
            }
            $contract_count = $contract_count;

            //Log::info("spendwewrerdd+++++++++", array($contract_count));
            //$contract_spend = Hospital::totalSpend($contract_count,$contracts->payment_type_id);
            //Log::info("spenddd+++++++++", array($contract_spend));
            //$contract_paid = Hospital::totalPaid($contract_count);

            if (count($contract_count) > 0) {
                $return_data[] = [
                    "payment_type_id" => $payment_type->id,
                    "payment_type_name" => $payment_type->name,
                    "active_contract_count" => count($contract_count)
                ];
            }
        }
        return $return_data;
    }

    public static function fetch_payment_stats_for_hospital_users_performance_dashboard($user_id)
    {
        $query = PaymentType::
        select(DB::raw("distinct(payment_types.id),payment_types.name"))
            ->join("contracts", "contracts.payment_type_id", "=", "payment_types.id");
        $payment_types = $query->get();
        $return_data = array();
        foreach ($payment_types as $payment_type) {
            $contract_count = array();
            $contracts = DB::table("contracts")
                ->select("contracts.*")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("contracts.payment_type_id", "=", $payment_type->id)
                ->where("agreements.hospital_id", "<>", 45);
            foreach ($contracts as $contract) {

                $contract_count[] = $contract;
            }
            if (count($contract_count) > 0) {
                $return_data[] = [
                    "payment_type_id" => $payment_type->id,
                    "payment_type_name" => $payment_type->name,
                    "active_contract_count" => count($contract_count)
                ];
            }
        }
        return $return_data;
    }

    public static function get_active_agreements($id, $agreement_id)
    {
        $allAgreements = DB::table("agreements")
            ->select("agreements.id", "agreements.name")
            ->where("agreements.hospital_id", "=", $id)
            ->whereRaw("agreements.archived=0")
            ->whereRaw("agreements.is_deleted=0")
            /*->whereRaw("agreements.start_date <= now()")*/
            ->whereRaw("agreements.end_date >= now()")
            ->whereNull("agreements.deleted_at")
            ->orderBy("agreements.name")
            ->pluck("name", "id");

        $currentAgreement = DB::table("agreements")
            ->select("agreements.id", "agreements.name")
            ->where("agreements.hospital_id", "=", $id)
            ->where("agreements.id", "=", $agreement_id)
            ->orderBy("agreements.name")
            ->pluck("name", "id");

        foreach ($currentAgreement as $key => $value) {
            $allAgreements[$key] = $value;
        }

        return $allAgreements;
    }

    public static function get_assertation_text($id)
    {
        $hospital = Hospital::findOrFail($id);
        return $hospital->getAssertationText();
    }

    //Chaitraly::function to fetch contract types for performance dashboard filters

    public function getAssertationText()
    {
        return $this->assertation_text;
    }

    //Chaitraly:: function for fetching contract names

    public static function get_status_invoice_dashboard_display($user_id)
    {
        $status = DB::table("hospital_user")
            ->select(DB::raw("distinct(hospital_user.is_invoice_dashboard_display)"))
            ->where('hospital_user.user_id', '=', $user_id)
            ->get();
        $is_invoice_dashboard_display = '';
        foreach ($status as $status) {
            $is_invoice_dashboard_display = $status->is_invoice_dashboard_display;
        }
        return $is_invoice_dashboard_display;
    }

    public static function get_status_compliance_dashboard_display()
    {
        $hospitals = Auth::user()->hospitals;

        $compliance_on_off_flag = false;
        foreach ($hospitals as $hospital) {
            //check compliance toggle for each hospital if one of the set true then show dashboard button
            $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
            if ($compliance_on_off == 1) {
                $compliance_on_off_flag = true;
            }
        }

        if ($compliance_on_off_flag == true) {
            return 1;
        } else {
            return 0;
        }

    }

    public static function get_status_performance_dashboard_display()
    {
        $hospitals = Auth::user()->hospitals;

        $performance_on_off_flag = false;
        foreach ($hospitals as $hospital) {
            $performance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
            if ($performance_on_off == 1) {
                $performance_on_off_flag = true;
            }
        }

        if ($performance_on_off_flag == true) {
            return 1;
        } else {
            return 0;
        }

    }

    public static function update_invoice_display_status($userid, $status)
    {
        //$status_update = DB::table("hospital_user")->where('user_id', '=', )->update(array('' => DB:: ));
        $status_update = DB::table('hospital_user')
            ->where('user_id', '=', $userid)
            ->update(array('is_invoice_dashboard_display' => $status));
        if ($status_update) {
            $result["response"] = "success";
            $result["msg"] = Lang::get('hospitals.invoice_dashboard_display_success');
        } else {
            $result["response"] = "error";
            $result["msg"] = Lang::get('hospitals.invoice_dashboard_display_error');
        }

        return $result;
    }

    //Chaitraly::New function added to retrieve hospital names for hospital users

    public static function get_isInterfaceReady($id, $interface_type)
    {
        if ($interface_type == 1) {
            $hospitalInterface = HospitalInterfaceLawson::where('hospital_id', $id)->whereNull('deleted_at')->first();
            if ($hospitalInterface) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function approverlistForAllContracts($user_id, $hospital_id)
    {
        $contracts_data = array();
        $agreement_ids = array();
        //  $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        $allAgreements = DB::table("agreements")
            ->select("agreements.*", "hospitals.name as hospital_name")
            ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->where("agreements.hospital_id", "=", $hospital_id)
            //->whereIn("agreement_approval_managers_info.user_id",$proxy_check_id) //added this condition for checking with proxy approvers
            ->where("agreement_approval_managers_info.is_deleted", "=", 0)
            ->orderBy("agreements.hospital_id")
            ->orderBy("agreements.id")
            ->distinct()->get();

        if (count($allAgreements) > 0) {
            foreach ($allAgreements as $agreement) {
                $agreement_ids[] = $agreement->id;
            }

            $practice_info = Contract::get_agreements_contract_info($agreement_ids);
        } else {
            $practice_info = '';
        }
        $contracts_data[] = [
            /*'hospital_id' => $hospital_id,
            'hospital_name' => $hospital["hospital_name"],*/
            'contracts_info' => $practice_info
        ];

        return $contracts_data;
    }

    //new function for fetching payment stats for Payment Filter

    public static function updateExistingHospitalWithCustomeInvoiceId()
    {
        ini_set('max_execution_time', 6000);
        // currently id = 39 is for Abash Health system for local testing.
        // we need to replace lifepoint healthsystem id instead of 39.
        // $get_region_ids_for_life_point_helthsystem = HealthSystemRegion::where('health_system_id', '=', 39)->pluck('id')->toArray();
        $get_region_ids_for_life_point_helthsystem = HealthSystemRegion::where('health_system_id', '=', 6)->pluck('id')->toArray();
        $get_hospital_ids_for_region_life_point = RegionHospitals::whereIn('region_id', $get_region_ids_for_life_point_helthsystem)->pluck('hospital_id')->toArray();
        Hospital::whereIn('id', $get_hospital_ids_for_region_life_point)
            ->update(['invoice_type' => 1]);
        return 1;
    }

    //new function for fetching payment stats for Payment Filter on performance dashboard

    public static function postHospitalsContractTotalSpendAndPaid($hospital_id = 0)
    {
        ini_set('max_execution_time', 60000000);
        $query = DB::table("contract_types")
            ->select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();
        $total_contract_count = 0;

        if ($hospital_id > 0) {
            $hospitals = Hospital::select('hospitals.id')
                ->where('hospitals.id', '=', $hospital_id)
                ->get();
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->whereNull("hospitals.deleted_at")
                ->distinct()
                ->get();
        }

        foreach ($hospitals as $hospital) {
            foreach ($contract_types as $contract_type) {
                $contracts = DB::table("contracts")->select(DB::raw("contracts.*"))
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->where("contracts.contract_type_id", "=", $contract_type->id)
                    ->where("agreements.hospital_id", "=", $hospital->id)
                    ->whereNull('contracts.deleted_at')
                    ->distinct()
                    ->get();
                $total_contract_count = $total_contract_count + count($contracts);
                if (count($contracts) > 0) {
                    $contract_spend = Hospital::totalSpend($contracts, $contract_type);
                    $contract_paid = Hospital::totalPaid($contracts);

                    $check_exist = HospitalContractSpendPaid::select('*')
                        ->where('hospital_id', '=', $hospital->id)
                        ->where('contract_type_id', '=', $contract_type->id)
                        ->first();

                    if ($check_exist) {
                        // Update
                        HospitalContractSpendPaid::where('hospital_id', '=', $hospital->id)
                            ->where('contract_type_id', '=', $contract_type->id)
                            ->update(['contract_spend' => $contract_spend, 'contract_paid' => $contract_paid, 'active_contract_count' => count($contracts)]);
                    } else {
                        $hospital_contract_spend_paid = new HospitalContractSpendPaid;
                        $hospital_contract_spend_paid->hospital_id = $hospital->id;
                        $hospital_contract_spend_paid->contract_type_id = $contract_type->id;
                        $hospital_contract_spend_paid->contract_spend = $contract_spend;
                        $hospital_contract_spend_paid->contract_paid = $contract_paid;
                        $hospital_contract_spend_paid->active_contract_count = count($contracts);
                        $hospital_contract_spend_paid->save();
                    }
                }
            }
        }
        return $total_contract_count;
    }

    public static function totalSpend($contracts, $contract_type)
    {
        $contract_spend = 0;
        $now = new DateTime('now');
        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            $diff = $startEndDatesForYear['year_start_date']->diff($startEndDatesForYear['year_end_date']);
            $months = $diff->y * 12 + $diff->m + $diff->d / 30;
            //if ($contract_type->id == ContractType::ON_CALL) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                $contractAnnualSpend = ContractRate::findAnnualPerDiemSpend($contract->id, $startEndDatesForYear['year_start_date']);
                /*if($contract->weekday_rate > 0 || $contract->weekend_rate > 0 || $contract->holiday_rate > 0){
                    $totalWeeks = Agreement::week_between_two_dates($startEndDatesForYear['year_start_date']->format('m/d/Y'),$startEndDatesForYear['year_end_date']->format('m/d/Y'));
                    $d = date_parse_from_format("m/d/Y",$startEndDatesForYear['year_start_date']->format('m/d/Y'));
                    $de = date_parse_from_format("m/d/Y",$startEndDatesForYear['year_end_date']->format('m/d/Y'));
                    $holidays = Physician::getHolidaysForperiod($d["year"],$startEndDatesForYear['year_start_date']->format('m/d/Y'),$startEndDatesForYear['year_end_date']->format('m/d/Y'),$de["year"]);

                    $weekdayspend = ($contract->weekday_rate * 5)*$totalWeeks;
                    //$weekendspend = ($contract->weekend_rate * 5)*$totalWeeks; //change on 24 july 2019 for weekend is 2
                    $weekendspend = ($contract->weekend_rate * 2)*$totalWeeks;
                    $holidayspend = ($contract->holiday_rate * ($holidays['week']+$holidays['weekend']));
                    $weekdayspend = $weekdayspend-($contract->weekday_rate * $holidays['week']);
                    $weekendspend = $weekendspend-($contract->weekend_rate * $holidays['weekend']);
                    $contract_spend = $contract_spend + $weekdayspend + $weekendspend + $holidayspend;
                }elseif($contract->on_call_rate >0){
                    $contract_spend = $contract_spend + ($contract->on_call_rate * 365);
                }*/
                $contract_spend = $contract_spend + $contractAnnualSpend;
                //} elseif ($contract_type->id == ContractType::MEDICAL_DIRECTORSHIP) {
            } elseif ($contract->payment_type_id == PaymentType::HOURLY) {
                $contractAnnualSpend = ContractRate::findAnnualHourlySpend($contract->id, $startEndDatesForYear['year_start_date'], ContractRate::FMV_RATE);
                $contract_spend = $contract_spend + $contractAnnualSpend;
                //  $contract_spend = $contract_spend + ($contract->annual_cap * $contract->rate);
            } else {
                $contractAnnualSpend = ContractRate::findAnnualStipendSpend($contract->id, round($months), ContractRate::FMV_RATE);
                //$contract_spend = $contract_spend + (($contract->max_hours * (int) round($months)) * $contract->rate);
                $contract_spend = $contract_spend + $contractAnnualSpend;
            }
        }
        return $contract_spend;
    }

    public static function totalPaid($contracts)
    {
        $paid = 0;
        $now = new DateTime('now');
        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();
            $agreement_data = DB::table("agreements")
                ->select("agreements.start_date")
                ->where("agreements.id", "=", $contract->agreement_id)
                ->distinct()->first();
            $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
            $total_amount_paid = strtotime($year_start_date_formatted) == strtotime($agreement_data->start_date) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;
            $paid = formatNumber($paid + $total_amount_paid);
        }
        return $paid;
    }

    public static function updateHospitalCustomInvoice($hospital_id, $invoice_type)
    {
        $results = self::where('id', '=', $hospital_id)
            ->update(['invoice_type' => $invoice_type]);

        return $results;
    }

    public function practices()
    {
        return $this->hasMany('App\Practice');
    }

    public function state()
    {
        return $this->belongsTo('App\State');
    }

    public function agreements()
    {
        return $this->hasMany('App\Agreement');
    }

    public function reports()
    {
        return $this->hasMany('App\HospitalReport');
    }

    public function invoices()
    {
        return $this->hasMany('App\HospitalInvoice');
    }

    //function for all contracts approver pluck

    public function hasPrimaryUser()
    {
        return $this->getPrimaryUser() != null;
    }

    public function getPrimaryUser()
    {
        return $this->users()->wherePivot('primary', '=', true)->first();
    }

    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('primary');
    }

    public function getPasswordExpirationMonths()
    {
        return $this->password_expiration_months;
    }

    public function setPasswordExpirationMonths($value)
    {
        $this->password_expiration_months = $value;
    }
}
