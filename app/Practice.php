<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use App\Http\Controllers\Validations\PracticeValidation;
use Request;
use Redirect;
use Lang;
use StdClass;
use Illuminate\Support\Facades\Log;
use function App\Start\is_hospital_admin;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;

class Practice extends Model implements Auditable
{

    use SoftDeletes, \OwenIt\Auditing\Auditable;

    const Full_Day = 1;
    const Half_Day = 0.5;
    protected $table = 'practices';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function createPractice($hospital_id)
    {
        $hospital = Hospital::findOrFail($hospital_id);

        $validation = new PracticeValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $practice = new Practice();
        $practice->hospital_id = $hospital->id;
        $practice->practice_type_id = Request::input('practice_type');
        $practice->name = Request::input('name');
        $practice->npi = Request::input('npi');
        $practice->state_id = Request::input('state');

        if (!$practice->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('hospitals.create_practice_error')])
                ->withInput();
        }
        if (Request::input('note_count') > 0) {
            $index = 1;
            for ($i = 1; $i <= Request::input('note_count'); $i++) {
                if (Request::input("note" . $i) != '') {
                    $invoice_note = new InvoiceNote();
                    $invoice_note->note_type = InvoiceNote::PRACTICE;
                    $invoice_note->note_for = $practice->id;
                    $invoice_note->note_index = $index;
                    $invoice_note->note = Request::input("note" . $i);
                    $invoice_note->is_active = true;
                    $invoice_note->hospital_id = $hospital->id;
                    $invoice_note->save();
                    $index++;
                }
            }
        }

        return Redirect::route('hospitals.practices', $hospital->id)->with([
            'success' => Lang::get('hospitals.create_practice_success')
        ]);
    }

    public static function editPractice($id)
    {
        $practice = Practice::findOrFail($id);
        $hospital = Hospital::findOrFail($practice->hospital_id);
        $validation = new PracticeValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $practice->practice_type_id = Request::input('practice_type');
        $practice->name = Request::input('name');
        $practice->npi = Request::input('npi');
        $practice->state_id = Request::input('state');

        if (!$practice->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('practices.edit_error')])
                ->withInput();
        } else {
            // Clear the current primary practice manager.
            DB::table("practice_user")
                ->where("practice_id", "=", $practice->id)
                ->update(["primary" => false]);

            // Set the new primary hospital user.
            DB::table("practice_user")
                ->where("practice_id", "=", $practice->id)
                ->where("user_id", "=", Request::input("primary_manager_id"))
                ->update(["primary" => true]);

            /*update Practice notes*/
            $index = 1;
            if (Request::input('note_count') > 0) {
                for ($i = 1; $i <= Request::input('note_count'); $i++) {
                    if (Request::input("note" . $i) != '') {
                        $invoice_note_old = InvoiceNote::where("note_type", '=', InvoiceNote::PRACTICE)
                            ->where("note_for", '=', $practice->id)
                            ->where("note_index", '=', $index)
                            ->where("is_active", '=', true)
                            ->where("hospital_id", '=', $practice->hospital_id)
                            ->update(["note" => Request::input("note" . $i)]);
                        if (!$invoice_note_old) {
                            $invoice_note = new InvoiceNote();
                            $invoice_note->note_type = InvoiceNote::PRACTICE;
                            $invoice_note->note_for = $practice->id;
                            $invoice_note->note_index = $index;
                            $invoice_note->note = Request::input("note" . $i);
                            $invoice_note->is_active = true;
                            $invoice_note->hospital_id = $practice->hospital_id;
                            $invoice_note->save();
                        }
                        $index++;
                    }
                }
            }
            InvoiceNote::where("note_type", '=', InvoiceNote::PRACTICE)
                ->where("note_for", '=', $practice->id)
                ->where("note_index", '>=', $index)
                ->where("is_active", '=', true)
                ->where("hospital_id", '=', $practice->hospital_id)
                ->update(["is_active" => false]);
        }

        return Redirect::route('practices.edit', $practice->id)->with([
            'success' => Lang::get('practices.edit_success')
        ]);
    }

    /**
     * Lists the practices with the specified agreements and contract types.
     *
     * @param $agreements   an array of agreements
     * @param $contractType the contract type
     */
    public static function listByAgreements($agreements, $contractType)
    {
        if (empty($agreements)) {
            return [];
        }

        $query = DB::table("practices")
            ->select("practices.id as id", "practices.name as name")

            //drop column practice_id from table 'physicians' changes by 1254
            // ->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->whereIn("contracts.agreement_id", $agreements)
            ->orderBy("practices.name");

        if ($contractType != -1) {
            $query->where("contracts.contract_type_id", "=", $contractType);
        }

        return $query->pluck("name", "id");
    }

    public static function getPracticeOptions($hospitalId, $contractTypeId)
    {
        $query = DB::table("practices")
            ->select("practices.id as id", "practices.name as name")

            //drop column practice_id from table 'physicians' changes by 1254
            // ->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->where("agreements.hospital_id", "=", $hospitalId)
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy("practices.id")
            ->orderBy("practices.name");

        if ($contractTypeId != -1) {
            $query->where("contracts.contract_type_id", "=", $contractTypeId);
        }
        //Log::info('MyQuery List',array($query));
        return $query->pluck("name", "id");
    }

    public static function getApprovalUserPratice($user_id, $selected_manager, $selected_hospital, $hospital, $selected_agreement, $agreements)
    {
        // Log::info("Agreement.php , user_id,selected_manager,selected_hospital,hospital,selected_agreement,agreements",array($user_id,$selected_manager,$selected_hospital,$hospital,$selected_agreement,$agreements));
        $default = ['0' => 'All'];
        $hospital_ids = array_keys($hospital);
        $agreement_ids = array_keys($agreements);

        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers

        //Log::info('hospital ids', array($hospital_ids));
        $practice = self::select('practices.id as id', 'practices.name as name')
            //->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            //->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join('physician_contracts', function ($join) {
                $join->on('physician_contracts.physician_id', '=', 'physician_practices.physician_id');

            })
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereNull('physician_practices.deleted_at')
            ->whereNull('physician_contracts.deleted_at');
        if ($selected_manager != -1) {
            $practice = $practice->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
                //->where('agreement_approval_managers_info.user_id', '=', $user_id)
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0');
            if ($selected_manager != 0) {
                $practice = $practice->where('agreement_approval_managers_info.type_id', '=', $selected_manager);
            }
        }
        if ($selected_hospital != 0) {
            $practice = $practice->where('practices.hospital_id', '=', $selected_hospital);
        } else {
            $practice = $practice->whereIn('practices.hospital_id', $hospital_ids);
        }
        if ($selected_agreement != 0) {
            $practice = $practice->where('agreements.id', '=', $selected_agreement);
        } else {
            $practice = $practice->whereIn('agreements.id', $agreement_ids);
        }
        $practice = $practice->where('agreements.archived', '=', false)
            ->orderBy("practices.name")
            ->distinct()
            ->pluck('name', 'id');
        $practice_list = array();
        /*$LogApproval=new LogApproval();*/
        foreach ($practice as $key => $value) {
            /*$logs=$LogApproval->logsForApproval($user_id,$selected_manager,0,0,0,$key);
            if(count($logs)>0)
            {*/
            if (!in_array($key, $practice_list)) {
                if ($selected_manager == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
                    $practice_ids = array();
                    foreach (Auth::user()->practices as $practice) {
                        $practice_ids[] = $practice->id;
                    }
                    if (count($practice_ids) == 0) {
                        $practice_ids[] = 0;
                    }
                    if (in_array($key, $practice_ids)) {
                        $practice_list[$key] = $value;
                    }
                } else {
                    $practice_list[$key] = $value;
                }
            }
            //}
        }
        // Log::info('My Practices:', array($practice_list));
        return $default + $practice_list;
    }

//<!-- added to show no of practices for one to many features by 1254 -->

    public static function getPractice($selected_hospital, $hospital, $selected_agreement, $agreements)
    {
        $default = ['0' => 'All'];
        $hospital_ids = array_keys($hospital);
        $agreement_ids = array_keys($agreements);
        // Log::info("selected_agreement",array($selected_agreement));
        // Log::info("agreement_ids",array($agreement_ids));


        //Log::info('hospital ids', array($hospital_ids));
        $practice = self::select('practices.id as id', 'practices.name as name')
            //->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            //->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.physician_id", "=", "physician_practices.physician_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereIn("agreements.id", $agreement_ids)
            ->whereNull('physician_practices.deleted_at');
        if ($selected_hospital != 0) {
            $practice = $practice->where('practices.hospital_id', '=', $selected_hospital);
        } else {
            $practice = $practice->whereIn('practices.hospital_id', $hospital_ids);
        }
        // if(count($selected_agreement)>0){
        //     // Log::info("(selected_agreement)",array($selected_agreement));
        //     // if(gettype($selected_agreement) === 'integer')
        //     // {
        //        // $practice = $practice->where('agreements.id','=', $selected_agreement);
        //     // }
        //     // else if(gettype($selected_agreement) === 'array')
        //     // {
        //        $practice = $practice->whereIn('agreements.id', $selected_agreement);
        //     // }
        // }else{
        //     $practice = $practice->whereIn('agreements.id', $agreement_ids);
        // }

        $practice = $practice->whereIn('agreements.id', $agreement_ids);

        $practice = $practice->where('agreements.archived', '=', false)
            ->orderBy("practices.name")
            ->distinct()
            ->pluck('name', 'id');
        $practice_list = array();
        /*$LogApproval=new LogApproval();*/
        foreach ($practice as $key => $value) {
            /*$logs=$LogApproval->logsForApproval($user_id,$selected_manager,0,0,0,$key);
            if(count($logs)>0)
            {*/
            if (!in_array($key, $practice_list)) {
                $practice_list[$key] = $value;
            }
            //}
        }
        //Log::info('My Practices:', array($practice_list));
        return $practice_list;
    }

    public static function getPracticeContracts($practiceId, $contractId)
    {


        $practice = Practice::findOrFail($practiceId);

        $data['practice'] = $practice;

        $getAgreementID = DB::table('contracts')
            ->where('id', '=', $contractId)
            ->pluck('agreement_id');
        $agreement = Agreement::findOrFail($getAgreementID);

        $data['contracts'] = $practice->getContractsForAgreement($agreement);

        $physicians_array = array();

        $override_mandate_details = HospitalOverrideMandateDetails::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $practice->hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        $hospital_time_stamp_entries = HospitalTimeStampEntry::select('hospital_id', 'action_id')
            ->where("hospital_id", "=", $practice->hospital_id)
            ->where('is_active', '=', 1)
            ->get();

        foreach ($data['contracts'] as $contracts) {

            if ($contractId == $contracts->id) {
                $contract_name = $contracts->name;
            }
            foreach ($contracts->practices as $practiceInContract) {
                // log::info('$practiceInContract', array($practiceInContract));
                if ($practiceInContract->id == $practice->id) {
                    foreach ($practiceInContract->physicians as $physicians) {
                        if ($contracts->id == $contractId) {

                            $contractNameId = ContractName::where("name", "=", $contracts->name)->firstOrFail();

                            // This query will return multiple contracts under the physician under practice under agreement with same contract name which might give wrong data.
                            // To avoid this we should not create contracts with same name under same physician. #Akash

                            $contractForPhysician = Contract::select('contracts.*')
                                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                                ->where('physician_contracts.physician_id', '=', $physicians->id)
                                ->where('physician_contracts.practice_id', '=', $practice->id)
                                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                                ->where('contracts.agreement_id', '=', $getAgreementID)
                                ->where('contracts.contract_name_id', '=', $contractNameId->id)->get();

                            // This query is commented but should be consider if the above query causing the issue. #Akash
                            // $contractForPhysician =Contract::where('id', '=', $contractId)->get();

                            // log::info('$contractForPhysician', array($contractForPhysician));
                            $physicians_array[] = [
                                "id" => $physicians->id,
                                "name" => $physicians->name,
                                "contract" => $contractForPhysician[0]->id,
                                "mandate_details" => $physicians->mandate_details,
                                "contractName" => $contracts->name,
                                "valid_upto" => $physicians->valid_upto,
                                "holiday_on_off" => $contractForPhysician[0]->holiday_on_off    // physicians log the hours for holiday activity on any day
                            ];
                        }


                    }
                }
            }
        }
        $contract = Contract::findOrFail($contractId);
        $contractType = $contract->contract_type_id;
        $contractPaymentType = $contract->payment_type_id;

        $data['contract_name'] = $contract_name;
        // Log::Info('physicians', array($physicians_array));
        $data['physicians'] = $physicians_array;

        $data['dates'] = Agreement::getAgreementData($agreement);

        $data['send_practice_id'] = $practiceId;
        $data['send_contract_id'] = $contractId;
        $data['send_agreement_id'] = $getAgreementID;

        $data['send_contract_type_id'] = $contractType;
        $data['send_payment_type_id'] = $contractPaymentType;
        $data['current_month'] = $data['dates']->current_month;

        $data['start_date'] = $data['dates']->start_date;
        $data['practice_id'] = $practiceId;
        $data['contract_id'] = $contractId;
        $data['agreement_id'] = $getAgreementID;
        $data['hospitals_override_mandate_details'] = $override_mandate_details;

        $period_label = "Period";
        $period_type = "Period";
        $period_frequency_max = "Period";

        if ($contract->payment_type_id == 1 || $contract->payment_type_id == 2 || $contract->payment_type_id == 6
            || $contract->payment_type_id == 7 || $contract->payment_type_id == 8) {
            $period_label = "Monthly";
            $period_type = "Month";
            $period_frequency_max = "Monthly";
        } else
            if ($agreement[0]->payment_frequency_type == 1) {
                $period_label = "Monthly";
                $period_type = "Month";
                $period_frequency_max = "Monthly";
            } else if ($agreement[0]->payment_frequency_type == 2) {
                $period_label = "Weekly";
                $period_type = "Week";
                $period_frequency_max = "Weekly";
            } else if ($agreement[0]->payment_frequency_type == 3) {
                $period_label = "Biweekly";
                $period_type = "Bi-week";
                $period_frequency_max = "Biweekly";
            } else if ($agreement[0]->payment_frequency_type == 4) {
                $period_label = "Quarterly";
                $period_type = "Quarter";
                $period_frequency_max = "Quarterly";
            }

        if ($contract->quarterly_max_hours == 1) {
            $period_frequency_max = "Quarterly";
        }

        $data['period_min_lable'] = $period_label;
        $data['period_max_lable'] = $period_frequency_max;
        $data['payment_frequency_lable'] = $period_type;
        $data['hospital_time_stamp_entries'] = $hospital_time_stamp_entries;
        $data['quarterly_max_hours'] = $contract->quarterly_max_hours;

        return $data;
    }

    private function getContractsForAgreement($agreement)
    {
        $data = [];

        $contracts = Contract::where('agreement_id', '=', $agreement[0]->id)
            ->where('end_date', '=', "0000-00-00 00:00:00")
            // ->groupBy('contract_name_id')
            // ->groupBy('contract_type_id')
            ->get();


        foreach ($contracts as $contract) {
            $contract_data = new StdClass();
            $contract_data->id = $contract['id'];
            $contract_data->name = contract_name($contract);
            $contract_data->practices = [];
            $contract_data->contract_type_id = $contract->contract_type_id;
            $contract_data->payment_type_id = $contract->payment_type_id;
            $contract_data->holiday_on_off = $contract->holiday_on_off;     // physicians log the hours for holiday activity on any day

            $practices = Practice::select('practices.*')
                //added to show physician link for existing physician
                // ->join('physicians', 'physicians.practice_id', '=', 'practices.id')
                //->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
                ->join('physician_contracts', 'physician_contracts.practice_id', '=', 'practices.id')
                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                ->where('contracts.agreement_id', '=', $agreement[0]->id)
                ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                ->where('contracts.payment_type_id', '=', $contract->payment_type_id)
                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                ->groupBy('practices.id')
                ->orderBy('practices.name')
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
                    DB::raw("contracts.manual_contract_valid_upto as contract_manual_contract_valid_upto"),
                    DB::raw("contracts.manual_contract_end_date as contract_manual_contract_end_date"),
                    DB::raw("contracts.mandate_details as mandate_details"))
                    //added to show physician link for existing physician
                    ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
                    ->join('physician_practices', function ($join) {
                        $join->on('physician_practices.physician_id', '=', 'physician_contracts.physician_id');
                        $join->on('physician_practices.practice_id', '=', 'physician_contracts.practice_id');

                    })
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                    ->where('contracts.agreement_id', '=', $agreement[0]->id)
                    ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                    ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                    ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                    // ->where('physicians.practice_id', '=', $practice->id)
                    ->where('physician_practices.practice_id', '=', $practice->id)
//                    ->where('contracts.practice_id', '=', $practice->id)
                    ->whereNull('physician_practices.deleted_at')
                    ->orderBy('physicians.first_name')
                    ->orderBy('physicians.last_name')
                    ->whereNull('contracts.deleted_at')
                    //issue fixed :Duplicate physician name appears in the log entry list by 1254 : added distinct()
                    ->distinct()
                    ->get();

                foreach ($physicians as $index => $physician) {
                    $today = date('Y-m-d');
                    $valid_upto = $physician->contract_manual_contract_valid_upto;
                    if ($valid_upto == '0000-00-00') {
                        $valid_upto = $physician->contract_manual_contract_end_date;
                    }
                    $physician_data = new StdClass();
                    $physician_data->id = $physician->id;
                    $physician_data->contract_id = $physician->contract_id;
                    $physician_data->mandate_details = $physician->mandate_details;
                    $physician_data->name = "{$physician->last_name}, {$physician->first_name}";
                    $physician_data->first = $index == 0;
                    $physician_data->valid_upto = $valid_upto;
                    $practice_data->physicians[] = $physician_data;
                }

                $contract_data->practices[] = $practice_data;
            }

            $data[] = $contract_data;
        }
        return $data;
    }

    public static function getRecentLogs()
    {

    }

    public static function deleteManager($practice_id, $manager_id)
    {
        $managerDelete = DB::table("practice_user")
            ->Where("practice_id", "=", $practice_id)
            ->Where("user_id", "=", $manager_id)
            ->delete();
        return $managerDelete;
    }

    public function hospital()
    {
        return $this->belongsTo('App\Hospital');
    }

    public function practiceType()
    {
        return $this->belongsTo('App\PracticeType');
    }

    public function specialty()
    {
        return $this->belongsTo('App\Specialty');
    }

    public function physicians()
    {
        return $this->hasMany('App\Physician');
    }

    public function physicianspractices()
    {
        return $this->hasMany('App\PhysicianPractices');
    }

    public function state()
    {
        return $this->belongsTo('App\State');
    }

    //Chaitray::Retrieve practices for Performance Dashboard

    public function reports()
    {
        return $this->hasMany('App\PracticeReport');
    }

    public function managrReports()
    {
        return $this->hasMany('App\practiceManagerReport');
    }

    public function hasPrimaryManager()
    {
        return $this->getPrimaryManager() != null;
    }

    public function getPrimaryManager()
    {
        return $this->users()->wherePivot('primary', '=', true)->first();
    }

    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('primary');
    }
}
