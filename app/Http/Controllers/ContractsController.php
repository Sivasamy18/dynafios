<?php

namespace App\Http\Controllers;

use App\Contract;
use App\PhysicianContracts;
use App\PhysicianLog;
use App\Amount_paid;
use App\ContractName;
use App\Physician;
use App\Agreement;
use App\Group;
use App\User;
use App\ApprovalManagerType;
use App\ApprovalManagerInfo;
use App\Hospital;
use App\Practice;
use App\ActionContract;
use App\ContractDeadlineDays;
use App\ContractPsaMetrics;
use App\ContractPsaWrvuRates;
use App\PhysicianInterfaceLawsonApcinvoice;
use App\ContractInterfaceLawsonApcdistrib;
use App\InterfaceType;
use App\LogApproval;
use App\LogApprovalHistory;
use App\ContractRate;
use App\PaymentType;
use App\PhysicianPractices;
use App\InvoiceNote;
use App\SplitPaymentPercentage;
use App\Http\Controllers\Validations\ContractInterfaceValidation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\SortingContractName;
use App\Action;
use App\SortingContractActivity;
use App\CustomCategoryActions;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\contract_document_path;

class ContractsController extends BaseController
{
    protected $requireAuth = true;

    //physician to multiple hosptial by 1254 added pid
    public function getEdit(Contract $contract, Practice $practice = null, Physician $physician)
    {
        $id = $contract->id;
        $pid = $practice->id;
        $physician_id = $physician->id;
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $data = Contract::getEdit($id, $pid, $physician_id);
        //physician to multiple hosptial by 1254
        $practice = Practice::findOrFail($pid);
        // $result_hospital = Hospital::findOrFail($practice->hospital_id);
        $data['practice'] = $practice;

        $agreement = Agreement::findOrFail($data['contract']->agreement_id);
        $hospitals_physicians = Physician::getAllPhysiciansForHospital($agreement->hospital_id);

        $contract_physicians = DB::table('physicians')
            ->select('physicians.id', DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), 'practices.id as practice_id', 'practices.name as practice_name')
//            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_contracts.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->where('physician_contracts.contract_id', '=', $data['contract']->id)
            ->whereNull('physician_contracts.deleted_at')
            ->whereNull('physicians.deleted_at')
            ->get();
        $data['hospitals_physicians'] = $hospitals_physicians;
        $contracts_physician_arr = $contract_physicians->pluck('id')->toArray();
        if (count($hospitals_physicians) > 0) {
            $hospitals_physicians = $hospitals_physicians->whereNotIn('id', $contracts_physician_arr)->all();
        }

        $data['hospitals_physicians'] = $hospitals_physicians;
        $data['contract_physicians'] = $contract_physicians;
        // $data['invoice_type']=$result_hospital->invoice_type;
        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('contracts/edit')->with($data);
    }

    //physician to multiple hospital by 1254
    public function postEdit($id, $pid = 0, $physician_id)
    {
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        // log::info("in post edit controller");
        //$contract = Contract::postEdit($id);

        //Action-Redesign by 1254
        $result = Contract::postEdit($id, $pid, $physician_id);
        return $result;


    }

    //physician to multiple hospital by 1254
    public function getDelete(Contract $contract, Practice $practice = null)
    {
        $id = $contract->id;
        $pid = $practice->id;

        $contract = Contract::findOrFail($id);
//Log::info('infooooo',array($contract->manager_info()));die();
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        //physican to multiple hospital by 1254
        $practice = Practice::findOrFail($pid);
        $data['practice'] = $practice;
        $checkPenddingLogs = PhysicianLog::penddingApprovalForContract($id);
        if ($checkPenddingLogs) {
            return Redirect::back()->with([
                'error' => Lang::get('contracts.approval_pending_error')
            ]);
        }
        $checkPenddingPayments = Amount_paid::penddingPaymentForContract($id);
        if ($checkPenddingPayments) {
            return Redirect::back()->with([
                'error' => Lang::get('contracts.payment_pending_error')
            ]);
        }

        if (!$contract->delete()) {
            return Redirect::back()->with(['error' => Lang::get('contracts.delete_error')]);
        } else {
            $contract->logs()->delete();
            foreach ($contract->manager_info() as $managers) {
                $managers->delete();
            }
        }

        return Redirect::back()->with(['success' => Lang::get('contracts.delete_success')]);
    }

    public function getContractDocument($contract_document)
    {
        //Log::info("getReport start");

        $filename = contract_document_path($contract_document);

        if (!file_exists($filename))
            App::abort(404);

        return Response::download($filename);
        //Log::info("getReport end");
    }

    public function getArchive(Contract $contract)
    {
        $id = $contract->id;
        if (!is_super_user())
            App::abort(403);

        $result = Contract::archiveUnrchive($id, true);
        return $result;
    }

    public function getUnarchive(Contract $contract)
    {
        $id = $contract->id;
        if (!is_super_user())
            App::abort(403);

        $result = Contract::archiveUnrchive($id, false);
        return $result;
    }

    public function interfaceDetails(Contract $contract, Practice $practice)
    {
        if (isset($contract->id) && $contract->id > 0) {
            $id = $contract->id;
        } else {
            // Contract id not found
            App::abort(403);
        }

        $practice_id = $practice->id;
        $contract = Contract::findOrFail($id);
        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;
        $physicianInterfaceDetailsLawson = PhysicianInterfaceLawsonApcinvoice::where('physician_id', $contract->physician->id)->whereNull('deleted_at')->first();
        $interfaceDetailsLawson = ContractInterfaceLawsonApcdistrib::where('contract_id', $id)->whereNull('deleted_at')->first();
        $data['interfaceType'] = 0;
        if (!$interfaceDetailsLawson) {
            $interfaceDetailsLawson = new ContractInterfaceLawsonApcdistrib();
            $data['is_lawson_interfaced'] = true;
        } else {
            $data['interfaceType'] = 1;
            $data['is_lawson_interfaced'] = $contract->is_lawson_interfaced;
        }

        if ($data['interfaceType'] == 0) {
            $data['interfaceType'] = 1;
        }

        $data['physicianIsLawsonInterfaceReady'] = $contract->physician->get_isInterfaceReady($contract->physician->id, 1);
        $data['contract'] = $contract;
        $data['physician'] = $contract->physician;
        $data['interfaceDetailsLawson'] = $interfaceDetailsLawson;
        $data['physicianInterfaceDetailsLawson'] = $physicianInterfaceDetailsLawson;
        //USED TO CONTROL WHICH INTERFACE TYPES ARE AVAILABLE ON THE PHYSICIAN INTERFACE DETAILS FORM
        $data['interfaceTypes'] = InterfaceType::whereIn('id', [1])->pluck('name', 'id');

        return View::make('contracts/interfacedetails')->with($data);
    }

    //physician to multiple hospital by 1254
    public function postInterfaceDetails($id, $practice_id = 0)
    {
        $contract = Contract::findOrFail($id);
        //physician to multiple hospital by 1254
        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;

        $physician = $contract->physician;

        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $validation = new ContractInterfaceValidation();

        $interfaceType = Request::input("interface_type_id");

        if ($interfaceType == 1) {
            if (!$physician->get_isInterfaceReady($physician->id, 1)) {
                return Redirect::back()->with(['error' => Lang::get('contract_interface.physician_lawson_interface_not_ready')])->withInput();
            }
            $contractInterface = ContractInterfaceLawsonApcdistrib::where('contract_id', $id)->whereNull('deleted_at')->first();
            if ($contractInterface) {

                if (!$validation->validateEdit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
                $contractInterface->contract_id = $contract->id;
                $contractInterface->cvd_company = intval(Request::input("cvd_company"));
                $contractInterface->cvd_vendor = Request::input("cvd_vendor");
                $contractInterface->invoice_number_suffix = Request::input("invoice_number_suffix");
                $contractInterface->cvd_dist_company = intval(Request::input("cvd_dist_company"));
                $contractInterface->cvd_dis_acct_unit = Request::input("cvd_dis_acct_unit");
                $contractInterface->cvd_dis_account = intval(Request::input("cvd_dis_account"));
                $contractInterface->cvd_dis_sub_acct = Request::input("cvd_dis_sub_acct");
                $contractInterface->updated_by = $this->currentUser->id;
                if (!$contractInterface->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not update')]);
                } else {
                    $contract->is_lawson_interfaced = Request::input("is_lawson_interfaced");
                    $contract->save();
                    return Redirect::route('contracts.edit', [$contract, $practice])
                        ->with(['success' => Lang::get('contracts.edit_success')]);
                }
            } else {

                if (!$validation->validateCreate(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }

                $contractInterfacenew = new ContractInterfaceLawsonApcdistrib();
                $contractInterfacenew->contract_id = $contract->id;
                $contractInterfacenew->cvd_company = intval(Request::input("cvd_company"));
                $contractInterfacenew->cvd_vendor = Request::input("cvd_vendor");
                $contractInterfacenew->invoice_number_suffix = Request::input("invoice_number_suffix");
                $contractInterfacenew->cvd_dist_company = intval(Request::input("cvd_dist_company"));
                $contractInterfacenew->cvd_dis_acct_unit = Request::input("cvd_dis_acct_unit");
                $contractInterfacenew->cvd_dis_account = intval(Request::input("cvd_dis_account"));
                $contractInterfacenew->cvd_dis_sub_acct = Request::input("cvd_dis_sub_acct");
                $contractInterfacenew->created_by = $this->currentUser->id;
                $contractInterfacenew->updated_by = $this->currentUser->id;
                if (!$contractInterfacenew->save()) {
                    return Redirect::back()->with(['error' => Lang::get('Details not save')]);
                } else {
                    $contract->is_lawson_interfaced = Request::input("is_lawson_interfaced");
                    $contract->save();
                    return Redirect::route('contracts.edit', [$contract, $practice])
                        ->with(['success' => Lang::get('contracts.edit_success')]);
                }
            }
        }

    }

    public function getPsaEdit(Contract $contract)
    {
        $id = $contract->id;
        $data = Contract::getEditPsa($id);

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('contracts/editPsa')->with($data);
    }

    public function postPsaEdit($id)
    {
        $result = Contract::postEditPsa($id);
        return $result;


    }

    public function getCopyContract(Contract $contract, Practice $practice = null, Physician $physician = null)
    {
        $id = $contract->id;
        $practice_id = $practice->id;
        $physician_id = $physician->id;
        //get active agreements
        $contract = Contract::findOrFail($id);
        $physician = Physician::findOrFail($physician_id);
        $agreement = $contract->agreement->id;

        $hospital_id = $contract->agreement->hospital->id;

        $agreements = Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where('is_deleted', '=', false)
            ->whereRaw("end_date >= now()")
            ->orderBy('name', 'asc')
            ->pluck('name', 'id');

        $practices = Practice::where('hospital_id', '=', $hospital_id)->pluck('name', 'id');

        $physicians = Physician::select(
            DB::raw("physicians.id"),
            DB::raw("concat(last_name, ', ', first_name) as physician_name")
        )
            ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->where('physician_practices.hospital_id', '=', $hospital_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->whereIn('physician_practices.practice_id', array_keys($practices->toArray()))
            ->orderBy('physician_name', 'asc')
            ->pluck('physician_name', 'id')->toArray();

        $data = [];
        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;

        if ($practice->id > 0) {
            $practice = Practice::findOrFail($practice_id);
            $data['practice'] = $practice;


        }
        $data['contract'] = $contract;
        $data['agreements'] = $agreements;
        $data['agreement'] = $agreement;
        $data['physicians'] = $physicians;
        $data['physician'] = $physician;

        if (Request::input("physician_id", null) == null) {
            reset($physicians); //reset select the first element of an array and end selects the last element of an array.
            $selected_physician = key($physicians);
        } else {
            $selected_physician = Request::input("physician_id");
        }

        $practices = Practice::
        join("physician_practices", "physician_practices.practice_id", "=", "practices.id")
            ->where('physician_practices.hospital_id', '=', $hospital_id)
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->where('physician_practices.physician_id', '=', $selected_physician)
            ->pluck('practices.name', 'practices.id');
        $data['practices'] = $practices;

        if (Request::ajax()) {
            return Response::json($data);
        }
        return View::make('contracts/copycontract')->with($data);
    }

    //physician to multiple hospital by 1254 :1002
    public function postCopyContract($id = 0, $practice_id = 0, $physician_id = 0)
    {
        $contract_to_copy = Contract::findOrFail(Request::input('contract_id'));
        $contract_to_copy_agreement = Agreement::findOrFail($contract_to_copy->agreement_id);

        $new_practice_id = Request::input('practice_id');
        $new_physician_id = Request::input('physician_id');


        if (Request::input('physician_id') == null) {
            return Redirect::route('contracts.copycontract', [$id, $practice_id, $physician_id])
                ->with(['error' => Lang::get('contracts.copy_contract_error')]);
        }
        if (Request::input('practice_id') == null) {
            return Redirect::route('contracts.copycontract', [$id, $practice_id, $physician_id])
                ->with(['error' => Lang::get('contracts.copy_contract_practice_error')]);
        }

        $agreement_id = Request::input('agreement_id');
        $new_contract = $contract_to_copy->replicate();
//        $new_contract->physician_id = 0;
        $new_contract->agreement_id = $agreement_id;
        $agreement = Agreement::findOrFail($agreement_id);
        $hospital_id = $agreement->hospital_id;

        if ($contract_to_copy_agreement->payment_frequency_type != $agreement->payment_frequency_type) {
            return Redirect::route('contracts.copycontract', [$id, $practice_id, $physician_id])
                ->with(['error' => Lang::get('agreements.agreement_payment_frequency_error')]);
        }


        if ($new_contract->save()) {
            // 6.1.1.12 changes start.
            $new_contract->physicians()->attach([$new_contract->id => ['physician_id' => $new_physician_id, 'contract_id' => $new_contract->id, 'practice_id' => $new_practice_id, 'created_by' => Auth::user()->id, 'updated_by' => Auth::user()->id]]);
            // 6.1.1.12 changes end.

            $invoice_notes_contract = InvoiceNote::getInvoiceNotes(Request::input('contract_id'), InvoiceNote::CONTRACT, $hospital_id, 0);

            if (count($invoice_notes_contract) > 0) {
                foreach ($invoice_notes_contract as $note_index => $note) {
                    $invoice_note = new InvoiceNote();
                    $invoice_note->note_type = InvoiceNote::CONTRACT;
                    $invoice_note->note_for = $new_contract->id;
                    $invoice_note->note_index = $note_index;
                    $invoice_note->note = $note;
                    $invoice_note->is_active = true;
                    $invoice_note->hospital_id = $hospital_id;
                    $invoice_note->save();
                }
            }

            $new_agreement = Agreement::findOrFail($new_contract->agreement_id);
            $new_contract->manual_contract_end_date = $new_contract->manual_contract_end_date != null ? $new_contract->manual_contract_end_date : $new_agreement->end_date;
            //copy rates to contract rate table
            if ($new_contract->payment_type_id == PaymentType::PER_DIEM) {

                $new_contractOnCallRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->on_call_rate, ContractRate::ON_CALL_RATE);
                $new_contractCalledBackRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->called_back_rate, ContractRate::CALLED_BACK_RATE);
                $new_contractCalledInRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->called_in_rate, ContractRate::CALLED_IN_RATE);
                $new_contractWeekdayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->weekday_rate, ContractRate::WEEKDAY_RATE);
                $new_contractWeekendRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->weekend_rate, ContractRate::WEEKEND_RATE);
                $new_contractHolidayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->holiday_rate, ContractRate::HOLIDAY_RATE);
            } else if ($new_contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

                $contract_rate_with_range = ContractRate::where('contract_id', '=', Request::input('contract_id'))
                    ->where('effective_start_date', '>=', @mysql_date($contract_to_copy_agreement->start_date))
                    ->where('effective_end_date', '<=', @mysql_date($contract_to_copy_agreement->end_date))
                    ->where("status", "=", '1')
                    ->where("rate_type", "=", 8)
                    ->get();
                foreach ($contract_rate_with_range as $contract_rate_obj) {
                    $new_contract_rate = new ContractRate();

                    $new_contract_rate->rate = $contract_rate_obj->rate;
                    $new_contract_rate->effective_start_date = $agreement->start_date;
                    $new_contract_rate->effective_end_date = $agreement->end_date;
                    $new_contract_rate->contract_id = $new_contract->id;
                    $new_contract_rate->rate_type = $contract_rate_obj->rate_type;
                    $new_contract_rate->status = $contract_rate_obj->status;
                    $new_contract_rate->rate_index = $contract_rate_obj->rate_index;
                    $new_contract_rate->range_start_day = $contract_rate_obj->range_start_day;
                    $new_contract_rate->range_end_day = $contract_rate_obj->range_end_day;
                    $new_contract_rate->save();
                }

            } //Skip Professional Services Agreements (PSA)
            else if ($new_contract->payment_type_id == PaymentType::PSA) {
            } else if ($new_contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {
                $new_contractFMVRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->rate, ContractRate::MONTHLY_STIPEND_RATE);

            } else {
                $new_contractFMVRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $agreement->end_date, $new_contract->rate, ContractRate::FMV_RATE);
            }

            //copy actions
            $index = 0;
            $actions_to_copy = ActionContract::
            where('contract_id', '=', $contract_to_copy->id)
                ->get();
            foreach ($actions_to_copy as $action_contract) {
                $new_action_contract = $action_contract->replicate();
                $new_action_contract->contract_id = $new_contract->id;
                $new_action_contract->save();
            }

            // Sprint 6.1.9 Start
            $sorting_activities = SortingContractActivity::select('*')
                ->where('contract_id', '=', $contract_to_copy->id)
                ->where('is_active', '=', 1)
                ->get();

            foreach ($sorting_activities as $sorting_activity) {
                $sorting_contract = new SortingContractActivity();
                $sorting_contract->contract_id = $new_contract->id;
                $sorting_contract->category_id = $sorting_activity->category_id;
                $sorting_contract->action_id = $sorting_activity->action_id;
                $sorting_contract->sort_order = $sorting_activity->sort_order;
                $sorting_contract->save();
            }

            // Sprint 6.1.9 End

            // Added 6.1.13 START
            $max_sort_order = SortingContractName::select([DB::raw('MAX(sorting_contract_names.sort_order) AS max_sort_order')])
                ->where('sorting_contract_names.practice_id', '=', $new_practice_id)
                ->where('sorting_contract_names.physician_id', '=', $new_physician_id)
                ->where('sorting_contract_names.is_active', '=', 1)
                ->first();

            $sorting_contract = new SortingContractName();
            $sorting_contract->practice_id = $new_practice_id;
            $sorting_contract->physician_id = $new_physician_id;
            $sorting_contract->contract_id = $new_contract->id;
            $sorting_contract->sort_order = $max_sort_order['max_sort_order'] + 1;
            $sorting_contract->save();

            // Added 6.1.13 END

            // Sprint 6.1.1.5 custom headings as well as actions Start
            if ($new_contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_categories_actions = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract_to_copy->id)
                    ->where('is_active', '=', true)
                    ->get();

                if (count($custom_categories_actions) > 0) {
                    foreach ($custom_categories_actions as $custom_categories_action) {
                        $custom_category_action = new CustomCategoryActions();
                        $custom_category_action->contract_id = $new_contract->id;
                        $custom_category_action->category_id = $custom_categories_action->category_id;
                        $custom_category_action->category_name = $custom_categories_action->category_name;
                        $custom_category_action->action_id = $custom_categories_action->action_id;
                        $custom_category_action->action_name = $custom_categories_action->action_name;
                        $custom_category_action->created_by = Auth::user()->id;
                        $custom_category_action->updated_by = Auth::user()->id;
                        $custom_category_action->save();
                    }
                }
            }
            // Sprint 6.1.1.5 custom headings as well as actions End

            //copy contract approval structure
            $agreement_approval_managers_info_to_copy = ApprovalManagerInfo::
            where('agreement_id', '=', $contract_to_copy->agreement_id)
                ->where('contract_id', '=', $contract_to_copy->id)
                ->get();
            foreach ($agreement_approval_managers_info_to_copy as $agreement_approval_managers_info) {
                $new_agreement_approval_managers_info = $agreement_approval_managers_info->replicate();
                $new_agreement_approval_managers_info->agreement_id = $agreement;
                $new_agreement_approval_managers_info->contract_id = $new_contract->id;
                $new_agreement_approval_managers_info->save();
            }

            //copy contract deadline days
            $contract_deadline_days_to_copy = ContractDeadlineDays::
            where('contract_id', '=', $contract_to_copy->id)
                //->where('is_active','=','0')
                ->where('is_active', '=', '1')
                ->get();
            foreach ($contract_deadline_days_to_copy as $contract_deadline_days) {
                $new_contract_deadline_days = $contract_deadline_days->replicate();
                $new_contract_deadline_days->contract_id = $new_contract->id;
                $new_contract_deadline_days->save();
            }

            //copy contract_psa_metrics
            $contract_psa_metrics_to_copy = ContractPsaMetrics::
            where('contract_id', '=', $contract_to_copy->id)
                ->get();
            foreach ($contract_psa_metrics_to_copy as $contract_psa_metrics) {
                $new_contract_psa_metrics = $contract_psa_metrics->replicate();
                $new_contract_psa_metrics->contract_id = $new_contract->id;
                $new_contract_psa_metrics->save();
            }

            //copy contract_psa_wrvu_rates
            $contract_psa_wrvu_rates_to_copy = ContractPsaWrvuRates::
            where('contract_id', '=', $contract_to_copy->id)
                ->where('is_active', '=', true)
                ->get();
            foreach ($contract_psa_wrvu_rates_to_copy as $contract_psa_wrvu_rates) {
                $new_contract_psa_wrvu_rates = $contract_psa_wrvu_rates->replicate();
                $new_contract_psa_wrvu_rates->contract_id = $new_contract->id;
                $new_contract_psa_wrvu_rates->save();
            }
            //physician to multiple hospital by 1254 :1002
            $practice = Practice::findOrFail($practice_id);
            $data['practice'] = $practice;
            //redirect back to physicians/{id}/contracts route with new message
            //physician to multiple hospital by 1254 :1002 added $practice_id in route
            return Redirect::route('physicians.contracts', [$physician_id, $practice_id])
                ->with(['success' => Lang::get('contracts.copy_success')]);
        } else {
            return Redirect::back()->with([
                'error' => Lang::get('contracts.copy_contract_fail')
            ])->withInput();
        }
    }

    public function getUnapproveLogs(Contract $contract, Practice $practice = null, Physician $physician = null)
    {
        $id = $contract->id;
        $practice_id = $practice->id;
        $physician_id = $physician->id;

        $contract = Contract::findOrFail($id);
        $physician = Physician::findOrFail($physician_id);
        $agreement = Agreement::findOrFail($contract
            ->agreement->id);
        $hospital_id = Hospital::findOrFail($contract
            ->agreement
            ->hospital
            ->id);
        //get periods
        $periods = $contract->getContractPeriods($contract->id);
        //return view
        $data = [];
        //physician to multiple hosptial by 1254
        $practice = Practice::findOrFail($practice_id);
        $data['practice'] = $practice;
        if ($practice->id > 0) {
            $practice = Practice::findOrFail($practice_id);
            $data['practice'] = $practice;
            //drop column practice_id from table 'physicians' changes by 1254 : commented
            //$physician->practice->id=$practice_id;
            //$physician->practice->name=$practice->name;
            //end drop column practice_id from table 'physicians' changes by 1254 : commented
        }
        $data['contract'] = $contract;
        $data['agreement'] = $agreement;
        $data['physician'] = $physician;
        $data['period'] = $periods->dates[1];
        $data['periods'] = $periods;
        $custom_reason = ['-1' => 'Custom Reason'];
        $LogApproval = new LogApproval();
        $reasons = DB::table('rejected_log_reasons')->select('*')->where('is_custom_reason', '=', 0)->pluck("reason", "id");
        $data['reasons'] = $reasons->toArray() + $custom_reason;

        return View::make('contracts/unapprovelogs')->with($data);
    }

    public function postUnapproveLogs($id = 0, $practice_id = 0, $physician_id = 0)
    {
        $contract_id = Request::input('contract_id');
        $period = Request::input('period');
        $reason_id = Request::input('reason', 0);
        $custome_reason = Request::input('unapprove_custom_reason_text', '');
        if ($reason_id > 0) {
            $reason = DB::table('rejected_log_reasons')->select('*')->where('id', '=', $reason_id)->first();
            $custome_reason = $reason->reason;
        }

        $performed_by = Auth::user()->id;
        $result = PhysicianLog::postUnapproveLogs($id, $practice_id, $contract_id, $physician_id, $period, $performed_by, '', '', $reason_id, $custome_reason);

        if ($result['status'] == 'payment_error') {
            return Redirect::route('contracts.edit', [$result['contract_id'], $result['practice_id'], $physician_id])
                ->with(['error' => Lang::get('contracts.unapprove_error_payments')]);
        }

        if ($result['status'] == 'log_error') {
            return Redirect::route('contracts.edit', [$result['contract_id'], $result['practice_id'], $physician_id])
                ->with(['error' => Lang::get('contracts.unapprove_error_no_logs')]);
        }

        if ($result['status'] == 'success') {
            return Redirect::route('contracts.edit', [$result['contract_id'], $result['practice_id'], $physician_id])
                ->with(['success' => Lang::get('contracts.unapprove_success')]);
        }

    }

    public function getDisplayContractApprovers(Contract $contract, Physician $physician)
    {
        $id = $contract->id;
        $physician_id = $physician->id;
        $contracts = Contract::findOrFail($id);
        $contractName = ContractName::getName($contracts->contract_name_id);

        $physicians = Physician::select(DB::raw("concat(physicians.first_name, ' ', physicians.last_name) as physician_name"))
            ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
            ->where('physician_contracts.contract_id', '=', $id)
            ->get();

        $physician_fullname = array();
        foreach ($physicians as $physician) {
            array_push($physician_fullname, $physician->physician_name);
        }

        $physicianfullname = implode(', ', $physician_fullname);

        $agreement = Agreement::findOrFail($contracts->agreement_id);

        $hospital = $agreement->hospital;

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

        $invoice_receipient = explode(',', $agreement->invoice_receipient);

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';


        if ($contracts->default_to_agreement == '1') {
            $contract_id = 0;//when we are fetching all approval managers same as agreement
        } else {
            $contract_id = $id;// when we are fetching approval managers for the specific contract
        }

        $ApprovalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $contracts->agreement_id)
            ->where('contract_id', "=", $contract_id)
            ->where('is_deleted', '=', '0')
            ->orderBy('level')->get();

        $data = [
            'hospital' => $hospital,
            'agreement' => $agreement,
            'users' => $users,
            'groups' => $groups,
            'approval_manager_type' => $approval_manager_type,
            'ApprovalManagerInfo' => $ApprovalManagerInfo,
            'contract' => $contracts,
            'contractName' => $contractName,
            'physicianfullname' => $physicianfullname
        ];
        return View::make('contracts/display_contract_approvers')->with($data);
    }

    // code for  update contract approvers on submit button  by #1254

    public function updateContractApprovers($id)
    {

        $contract = Contract::findOrFail($id);
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $hospital_id = $agreement->hospital_id;
        $response = Contract::updateApprovers($id);

        if ($response["response"] === "error") {
            return Redirect::back()->with([
                'error' => $response["msg"]
            ])->withInput();
        } else {
            return Redirect::route('hospitals.approvers', $hospital_id)
                ->with(['success' => $response["msg"]]);
        }
    }

    public function contract_rate_update()
    {

        if (!is_super_user())
            App::abort(403);
        $contractRateUpdate = ContractRate::updateExistingContractsRate();
    }

    public function onetomany_update()
    {

        if (!is_super_user())
            App::abort(403);

        $onetomany_update = PhysicianPractices::updateExistingPracticePhysicianswithPhysician();
    }

    public function custome_invoice_update()
    {

        if (!is_super_user())
            App::abort(403);

        $custome_invoice_update = Hospital::updateExistingHospitalWithCustomeInvoiceId();
    }

    public function hospital_update_to_invoice_notes_table()
    {

        if (!is_super_user())
            App::abort(403);

        return $invoice_notes_update = InvoiceNote::updateExistingInvoiceNotesWithHospitalId();
    }

    /**
     * This function is used for updating the duration column value to log_hours column in physician_lof table.
     */
    public function update_log_hours()
    {

        if (!is_super_user())
            App::abort(403);
        $custome_invoice_update = PhysicianLog::copyDurationValueToLogHours();
    }

    public function update_partial_hours_calculation()
    {

        if (!is_super_user())
            App::abort(403);

        $update = Contract::updatePartialHoursCalculation();
    }

    public function paymentManagement(Contract $contract, Practice $practice = null, Physician $physician)
    {
        $id = $contract->id;
        $pid = $practice->id;
        $physician_id = $physician->id;

        $contract = Contract::findOrFail($id);
        $practice = Practice::findOrFail($pid);
        $hospital_id = $practice->hospital_id;

        $data['contract'] = $contract;
        $data['practice'] = $practice;
        $data['physician'] = Physician::findOrFail($physician_id);

        $results = SplitPaymentPercentage::where("contract_id", '=', $contract->id)
            ->where("hospital_id", '=', $hospital_id)
            ->where("is_active", '=', true)
            ->get();

        $return_data = array();

        foreach ($results as $result) {
            $return_data[] = [
                "id" => $result->id,
                "payment_percentage" => $result->payment_percentage,
                "payment_note_1" => $result->payment_note_1,
                "payment_note_2" => $result->payment_note_2,
                "payment_note_3" => $result->payment_note_3,
                "payment_note_4" => $result->payment_note_4
            ];
        }

        $data['split_payment_count'] = count($results);   // count($results);
        $data['return_data'] = $return_data;

        return View::make('contracts/paymentmanagement')->with($data);
    }

    public function postPaymentManagement($id, $pid = 0, $physician_id)
    {
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $result = SplitPaymentPercentage::postPaymentManagement($id, $pid, $physician_id);
        return $result;
    }

    public function updateSplitPayment()
    {
        $data = SplitPaymentPercentage::updateSplitPayment();
        return $data;
    }


    public function getPhysicianLogsInApprovalQueue(Agreement $agreement, Contract $contract)
    {
        $agreement_id = $contract->id;
        $contract_id = $agreement->id;

        if (Request::ajax()) {
            $data = PhysicianLog::getPhysicianLogsInApprovalQueue($agreement_id, $contract_id);
            return $data;
        }
    }

    public function updateSortingContractNames()
    {
        $data = SortingContractName::updateSortingContractNames();
        return $data;
    }

    public function updateSortingContractActivities()
    {
        $data = SortingContractName::updateSortingContractActivities();
        return $data;
    }

    public function UpdateCustomCategoriesActions($contract_id)
    {
        if (!is_super_user() && !is_super_hospital_user())
            App::abort(403);

        $request = $_POST;
        if (Request::ajax()) {
            $data = CustomCategoryActions::UpdateCustomCategoriesActions(Auth::user()->id, $contract_id, $request['category_id'], $request['action_id'], $request['category_action_name']);
            return $data;
        }
    }

    public function updateSortingContractNamesByHospital($hospital_id)
    {
        $data = SortingContractName::updateSortingContractNamesByHospital($hospital_id);
        return $data;

    }

    public function show(Contract $contract)
    {
        return view('contracts.show', compact('contract'));
    }
}
