<?php

namespace App;

use App\customClasses\PaymentFrequencyFactoryClass;
use App\Jobs\UpdatePendingPaymentCount;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use PDO;
use StdClass;
use function App\Start\is_hospital_admin;
use function App\Start\is_practice_manager;
use function App\Start\is_super_hospital_user;

class Agreement extends Model implements Auditable
{

    use SoftDeletes, \OwenIt\Auditing\Auditable;

    const MONTHLY = 1;
    const WEEKLY = 2;
    const BI_WEEKLY = 3;
    const QUARTERLY = 4;
    protected $table = "agreements";
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public static function getAgreementsPmtFrequency($hospital_id)
    {
        /* where clause of is_deleted added for soft delete
            Code modified_on: 07/04/2016
            */
        return Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where('is_deleted', '=', false)
            ->pluck('payment_frequency_type', 'id');
    }

    public static function getAgreementsForHospital($hospital_id)
    {
        /* where clause of is_deleted added for soft delete
            Code modified_on: 07/04/2016
            */
        return Agreement::where('hospital_id', '=', $hospital_id)
            ->where('archived', '=', false)
            ->where('is_deleted', '=', false)
            ->pluck('name', 'id', 'payment_frequency_type');
    }

    public static function getActiveAgreementsById($id)
    {
        if ($id) {
            return $selected_agreement = self::where('id', '=', $id)
                ->where('archived', '=', false)
                ->where('is_deleted', '=', false)
                ->pluck('name', 'id', 'payment_frequency_type');
        } else {
            return [];
        }
    }

    public static function delete_files($target)
    {
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                //Log::info('in foreaCXH');
                self::delete_files($file);
            }
            if (!file_exists($target)) {
                rmdir($target);
            }

            return 0;
        } elseif (is_file($target)) {
            if (!file_exists($target)) {
                unlink($target);
            }

            return 0;
        }
        return 0;
    }

    public static function createAgreement($hospital)
    {
        $result = array();
        $emailCheck = [];
        if (Request::input('emailCheck') != null) {
            $emailCheck = Request::input('emailCheck');
        }

        $agreement = new Agreement;
        $agreement->hospital_id = $hospital->id;
        $agreement->name = Request::input('name');
        $start_date = mysql_date(Request::input('start_date'));
        $end_date = mysql_date(Request::input('end_date'));
        $agreement->contract_manager = 0;
        $agreement->financial_manager = 0;
        $agreement->send_invoice_day = 0;
        $agreement->pass1_day = 0;
        $agreement->pass2_day = 0;
        $agreement->invoice_receipient = '';
        $agreement->invoice_reminder_recipient_1 = 0;
        $agreement->invoice_reminder_recipient_2 = 0;
        $agreement->payment_frequency_type = Request::input('payment_frequency_option');
        $agreement->payment_frequency_start_date = mysql_date(Request::input('frequency_start_date'));
        // $agreement->payment_frequency_start_date = mysql_date(Request::input('payment_start_date'));
        $contract_id = 0;
        $hospital_details = Hospital::findOrFail($agreement->hospital_id);


        /*condition for start date & end date is added, i.e. start date should be always less than or equal to end date
            on 22 April 2016 */
        if ($start_date <= $end_date) {
            $agreement->start_date = mysql_date(Request::input('start_date'));
            $agreement->end_date = mysql_date(Request::input('end_date'));

            /*condition for valid_upto date is added i.e. valid_upto date should not be less than end date &
            should not be greater than 90 days from end date
            on 20 April 2016 */

            $valid_upto = mysql_date(Request::input('valid_upto'));
            $date_after90_days_of_end_date = date('Y-m-d', strtotime('+90 day', strtotime($agreement->end_date)));
            if (($agreement->end_date <= $valid_upto) && ($valid_upto <= $date_after90_days_of_end_date)) {
                $agreement->valid_upto = mysql_date(Request::input('valid_upto'));
                if (Request::has("on_off")) {
                    if (Request::input("on_off") == 1) {
                        $agreement->approval_process = Request::input('on_off');
                    } else {
                        //$agreement->approval_process = 0;
                        $agreement->approval_process = Request::input('on_off');
                    }
                } else {
                    $agreement->approval_process = 0;
                }
                //Common code save for ON-OFF approval process //remove from if-else. edited by 1086 10.09.18
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    $agreement->invoice_reminder_recipient_1 = Request::input('invoice_reminder_recipient1');
                    $agreement->invoice_reminder_recipient_2 = Request::input('invoice_reminder_recipient2');
                    $agreement->invoice_reminder_recipient_1_opt_in_email = Request::has('emailCheck_recipient_1') ? 1 : 0;
                    $agreement->invoice_reminder_recipient_2_opt_in_email = Request::has('emailCheck_recipient_2') ? 1 : 0;
                    $agreement->send_invoice_day = Request::input('send_invoice_reminder_day');// this will be used for invoice reminder day
                    $agreement->invoice_receipient = Request::input('invoice_receipient1') . ',' . Request::input('invoice_receipient2') . ',' . Request::input('invoice_receipient3');
                } else {
                    $agreement->invoice_reminder_recipient_1 = "";
                    $agreement->invoice_reminder_recipient_2 = "";
                    $agreement->invoice_reminder_recipient_1_opt_in_email = '0';
                    $agreement->invoice_reminder_recipient_2_opt_in_email = '0';
                    log::info("hospitals_datails");
                    $agreement->send_invoice_day = '0';// this will be used for invoice reminder day
                    $agreement->invoice_receipient = "";
                }
                if (!$agreement->save()) {
                    $result["response"] = "error";
                    $result["msg"] = Lang::get('hospitals.create_agreement_error');
                } else {
                    $internal_note_insert = new AgreementInternalNote;
                    $internal_note_insert->agreement_id = $agreement->id;
                    $internal_note_insert->note = Request::input('internal_notes');
                    $internal_note_insert->save();
                    //Write code for approval manager conditions & to save them in database
                    $agreement_id = $agreement->id;//fetch agreement id
                    $approval_manager_info = array();
                    $approval_level = array();
                    $levelcount = 0;
                    //Fetch all levels of approval managers & remove NA approvaal levels
                    for ($i = 1; $i < 7; $i++) {
                        // if(Request::input('approverTypeforLevel'.$i)!=0)
                        if (Request::input('approval_manager_level' . $i) != 0) {

                            // $approval_level[$levelcount]['approvalType']=Request::input('approverTypeforLevel'.$i);
                            $approval_level[$levelcount]['approvalType'] = 0; // This change is done for custom title by akash.
                            $approval_level[$levelcount]['level'] = $levelcount + 1;
                            $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                            $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                            $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                            $approval_level[$levelcount]['emailCheck'] = count($emailCheck) > 0 ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                            $levelcount++;
                        }
                    }
                    // asort($approval_level);//Sorting on basis of type of approval level
                    $approval_level_number = 1;
                    $fail_to_save_level = 0;
                    foreach ($approval_level as $key => $approval_level) {
                        // code...
                        $agreement_approval_manager_info = new ApprovalManagerInfo;
                        $agreement_approval_manager_info->contract_id = $contract_id;
                        $agreement_approval_manager_info->agreement_id = $agreement_id;
                        // $agreement_approval_manager_info->level=$approval_level_number;
                        $agreement_approval_manager_info->level = $approval_level['level'];
                        $agreement_approval_manager_info->type_id = $approval_level['approvalType'];
                        $agreement_approval_manager_info->user_id = $approval_level['approvalManager'];
                        $agreement_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                        $agreement_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                        $agreement_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                        $agreement_approval_manager_info->is_deleted = '0';

                        if (!$agreement_approval_manager_info->save()) {
                            $fail_to_save_level = 1;
                        } else {
                            //success
                            $approval_level_number++;
                        }
                    }//End of for loop
                    if ($fail_to_save_level == 1) {
                        //if fails while saving approval level, delete all the approval levels & agreement as well
                        //Delete all the entries from approval levels for the agreement
                        DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)->forceDelete();
                        DB::table('agreements')->where('id', "=", $agreement_id)->delete();
                        $result["response"] = "error";
                        $result["msg"] = Lang::get('hospitals.create_agreement_error');
                    } else {
                        $result["response"] = "success";
                        $result["msg"] = Lang::get('hospitals.create_agreement_success');
                    }

                }
            } else {
                $result["response"] = "error";
                $result["msg"] = Lang::get('agreements.valid_upto_date_error');
            }
        } else {
            $result["response"] = "error";
            $result["msg"] = Lang::get('agreements.start_date_end_date_error');
        }
        return $result;
    }

    public static function updateAgreement($agreement)
    {
        //log::info("hospitals_details");
        $result = array();
        $emailCheck = [];
        if (Request::input('emailCheck') != null) {
            $emailCheck = Request::input('emailCheck');
        }
        //log::info("hospitals_details");
        $agreement->name = Request::input('name');
        $agreement->payment_frequency_start_date = mysql_date(Request::input('frequency_start_date'));
        $agreement_pev_start_date = $agreement->start_date;
        $agreement_pev_end_date = $agreement->end_date;
        $start_date = mysql_date(Request::input('start_date'));
        $end_date = mysql_date(Request::input('end_date'));
        $contract_id = 0;
        $hospital_details = Hospital::findOrFail($agreement->hospital_id);
        /*condition for start date & end date is added, i.e. start date should be always less than or equal to end date
           on 22 April 2016 */

        if ($start_date <= $end_date) {
            /* check for logs are present after selected dates*/
            $present_logs = PhysicianLog::join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->where('contracts.agreement_id', '=', $agreement->id)->where('physician_logs.date', '>', $end_date)->get();
            if (count($present_logs) == 0) {
                $valid_upto = mysql_date(Request::input('valid_upto'));
                /* check for contracts not having default dates are having end date and valid upto date after selected dates*/
                $present_contracts = Contract::where('agreement_id', '=', $agreement->id)
                    ->where('default_to_agreement_dates', '=', 0)
                    ->where(function ($query) use ($end_date, $valid_upto) {
                        $query->where('manual_contract_end_date', '>', $end_date)
                            ->orWhere('manual_contract_valid_upto', '>', $valid_upto);
                    })
                    ->get();
                if (count($present_contracts) == 0) {
                    $agreement->start_date = mysql_date(Request::input('start_date'));
                    $agreement->end_date = mysql_date(Request::input('end_date'));

                    /*condition for valid_upto date is added i.e. valid_upto date should not be less than end date
                     & should not be greater than 90 days from end date
                     on 20 April 2016         */

                    if ($valid_upto == "0000-00-00") {
                        $valid_upto = $agreement->end_date;
                    }

                    $date_after90_days_of_end_date = date('Y-m-d', strtotime('+90 day', strtotime($agreement->end_date)));

                    if (($agreement->end_date <= $valid_upto) && ($valid_upto <= $date_after90_days_of_end_date)) {
                        $agreement->valid_upto = mysql_date(Request::input('valid_upto'));
                        if ($agreement->valid_upto == "0000-00-00") {
                            $agreement->valid_upto = $agreement->end_date;
                        }
                        if (Request::has("on_off")) {
                            if (Request::input("on_off") == 1) {
                                $agreement->approval_process = Request::input('on_off');
                                //$agreement->contract_manager = Request::input('contract_manager');
                                //$agreement->financial_manager = Request::input('financial_manager');
                                //$agreement->pass1_day = Request::input('initial_review_day');
                                //$agreement->pass2_day = Request::input('final_review_day');
                            } else {
                                //$agreement->approval_process = 0;
                                $agreement->approval_process = Request::input('on_off');
                            }
                        } else {
                            $agreement->approval_process = 0;

                        }

                        //Common code edit for ON-OFF approval process //remove from if-else. edited by 1086 10.09.18
                        if ($hospital_details->invoice_dashboard_on_off == 1) {
                            $agreement->invoice_reminder_recipient_1 = Request::input('invoice_reminder_recipient1');
                            $agreement->invoice_reminder_recipient_2 = Request::input('invoice_reminder_recipient2');
                            $agreement->invoice_reminder_recipient_1_opt_in_email = Request::has('emailCheck_recipient_1') ? '1' : '0';
                            $agreement->invoice_reminder_recipient_2_opt_in_email = Request::has('emailCheck_recipient_2') ? '1' : '0';
                            $agreement->send_invoice_day = Request::input('send_invoice_reminder_day');
                            $agreement->invoice_receipient = Request::input('invoice_receipient1') . ',' . Request::input('invoice_receipient2') . ',' . Request::input('invoice_receipient3');
                        } else {
                            $agreement->invoice_reminder_recipient_1 = "";
                            $agreement->invoice_reminder_recipient_2 = "";
                            $agreement->invoice_reminder_recipient_1_opt_in_email = '0';
                            $agreement->invoice_reminder_recipient_2_opt_in_email = '0';
                            $agreement->send_invoice_day = Request::input('28');
                            $agreement->invoice_receipient = "";

                        }
                        $internal_note = AgreementInternalNote::
                        where('agreement_id', '=', $agreement->id)
                            ->first();
                        if ($internal_note != null) {
                            $internal_note->note = Request::input('internal_notes');
                            $internal_note->save();
                        } else {
                            $internal_note_insert = new AgreementInternalNote;
                            $internal_note_insert->agreement_id = $agreement->id;
                            $internal_note_insert->note = Request::input('internal_notes');
                            $internal_note_insert->save();
                        }

                        if (!$agreement->save()) {

                            /*return Redirect::back()->with([
                                'error' => Lang::get('agreements.edit_error')
                            ])->withInput();*/

                            $result["response"] = "error";
                            $result["msg"] = Lang::get('agreements.edit_error');
                        } else {
                            if (strtotime($agreement_pev_start_date) != strtotime($agreement->start_date)) {
                                $amount_paid = new Amount_paid();
                                $paid = $amount_paid->changeDate($agreement->start_date, $agreement->end_date, $agreement->id);
                            }

                            /**
                             * get all active contract_ids associated with the agreement for rate effectice start and end date updates
                             */
                            $contract_with_agreement = Contract::select("id")
                                ->where("contracts.default_to_agreement_dates", "=", "1")
                                ->whereNull("contracts.deleted_at")
                                ->where("contracts.agreement_id", '=', $agreement->id)
                                ->get();

                            if ($contract_with_agreement) {
                                /***
                                 * It takes the contract_ids array and return the active contract rates for those contracts.
                                 */

                                foreach ($contract_with_agreement as $contract_with_selected_agreement) {
                                    $contract_rates_obj = ContractRate::whereIn('effective_end_date', function ($query) use ($contract_with_selected_agreement) {
                                        $query->selectRaw('MAX( effective_end_date)')
                                            ->from('contract_rate')
                                            ->where('contract_id', $contract_with_selected_agreement->id)
                                            ->where('status', '1')
                                            ->groupBy('rate_type');
                                    })
                                        ->where('contract_id', $contract_with_selected_agreement->id)
                                        ->where('status', '1')
                                        ->get();
                                    if (count($contract_rates_obj) > 0) {
                                        /***
                                         * update the effective_end date of all the rates.
                                         */
                                        ContractRate::whereIn('id', $contract_rates_obj->pluck('id')->toArray())->update(["effective_end_date" => $agreement->end_date, "updated_by" => Auth::user()->id]);
                                    }
                                }

                            }

                            Contract::where('agreement_id', '=', $agreement->id)
                                ->where('default_to_agreement_dates', '=', 1)
                                ->withTrashed()
                                ->update(['manual_contract_end_date' => $agreement->end_date, 'manual_contract_valid_upto' => $agreement->valid_upto]);
                            //Write code for approval manager conditions & to save them in database
                            $agreement_id = $agreement->id;//fetch agreement id
                            $approval_manager_info = array();
                            $levelcount = 0;
                            $approval_level = array();
                            for ($i = 1; $i < 7; $i++) {
                                if (Request::input('approval_manager_level' . $i) != 0) {

                                    $approval_level[$levelcount]['approvalType'] = Request::input('approverTypeforLevel' . $i, 0);
                                    $approval_level[$levelcount]['level'] = $levelcount + 1;
                                    $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                                    $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                                    $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                                    $approval_level[$levelcount]['emailCheck'] = (count($emailCheck) > 0) ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                                    $levelcount++;
                                }
                            }

                            $approval_level_number = 1;
                            $fail_to_save_level = 0;
                            //added for make status deleted for not selected levels for edit
                            DB::table('agreement_approval_managers_info')->where('level', ">", $levelcount)->where('agreement_id', '=', $agreement_id)->where('contract_id', '=', 0)->where('is_deleted', '=', '0')->update(array('is_deleted' => '1'));
                            foreach ($approval_level as $key => $approval_level) {
                                $approval_level_number = $approval_level['level'];
                                /*Query for level, fetch type & manager, if type & levels are matching update all other info,
                                if not matching, update flag is_deleted =1 & insert new row for info  */
                                $agreement_approval_manager_data = DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)
                                    ->where('contract_id', "=", 0)
                                    ->where('level', "=", $approval_level_number)
                                    ->where('is_deleted', '=', '0')
                                    ->first();
                                if (!empty($agreement_approval_manager_data) && $agreement_approval_manager_data->type_id == $approval_level['approvalType']) {

                                    $agreement_approval_manager_info = ApprovalManagerInfo::findOrFail($agreement_approval_manager_data->id);
                                    $agreement_approval_manager_info->agreement_id = $agreement_id;
                                    $agreement_approval_manager_info->level = $approval_level['level'];
                                    $agreement_approval_manager_info->type_id = $approval_level['approvalType'];
                                    $agreement_approval_manager_info->user_id = $approval_level['approvalManager'];
                                    $agreement_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                                    $agreement_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                                    $agreement_approval_manager_info->opt_in_email_status = (string)$approval_level['emailCheck'];
                                    $agreement_approval_manager_info->is_deleted = '0';

                                    if (!$agreement_approval_manager_info->save()) {
                                        $fail_to_save_level = 1;
                                    } else {
                                        if ($agreement_approval_manager_data->user_id != $approval_level['approvalManager']) {
                                            $contract_with_default = DB::table('contracts')->select("contracts.id")
                                                ->where("contracts.default_to_agreement", "=", "1")
                                                ->whereNull("contracts.deleted_at")
                                                ->where("contracts.agreement_id", "=", $agreement_id)->pluck("contracts.id");
                                            if (count($contract_with_default) > 0) {
                                                PhysicianLog::where("next_approver_level", "=", $agreement_approval_manager_data->level)
                                                    ->whereIN("contract_id", $contract_with_default)->update(array("next_approver_user" => $approval_level['approvalManager']));
                                            }
                                        }

                                        $approval_level_number++;
                                    }
                                } else {

                                    DB::table('agreement_approval_managers_info')->where('level', '=', $approval_level_number)->where('agreement_id', '=', $agreement_id)->where('contract_id', '=', 0)->where('is_deleted', '=', '0')->update(array('is_deleted' => '1'));


                                    if ($approval_level['approvalManager'] != 0) {
                                        $agreement_approval_manager_info = new ApprovalManagerInfo;
                                        $agreement_approval_manager_info->agreement_id = $agreement_id;
                                        $agreement_approval_manager_info->level = $approval_level_number;
                                        $agreement_approval_manager_info->type_id = $approval_level['approvalType'];
                                        $agreement_approval_manager_info->user_id = $approval_level['approvalManager'];
                                        $agreement_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                                        $agreement_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                                        $agreement_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                                        $agreement_approval_manager_info->is_deleted = '0';
                                        $agreement_approval_manager_info->contract_id = 0;
                                        if (!$agreement_approval_manager_info->save()) {
                                            $fail_to_save_level = 1;
                                        } else {
                                            if (!empty($agreement_approval_manager_data)) {
                                                if ($agreement_approval_manager_data->user_id != $approval_level['approvalManager']) {
                                                    $contract_with_default = DB::table('contracts')->select("contracts.id")
                                                        ->where("contracts.default_to_agreement", "=", "1")
                                                        ->whereNull("contracts.deleted_at")
                                                        ->where("contracts.agreement_id", "=", $agreement_id)->pluck("contracts.id");
                                                    if (count($contract_with_default) > 0) {
                                                        PhysicianLog::where("next_approver_level", "=", $agreement_approval_manager_data->level)
                                                            ->whereIN("contract_id", $contract_with_default)->update(array("next_approver_user" => $approval_level['approvalManager']));
                                                    }
                                                }
                                            }
                                            //success
                                            $approval_level_number++;
                                        }
                                    } else {
                                        //success
                                        $approval_level_number++;
                                    }
                                }


                            }//End of for loop
                            if ($fail_to_save_level == 1) {

                                $result["response"] = "error";
                                $result["msg"] = Lang::get('agreements.edit_error');
                            } else {
                                $result["response"] = "success";
                                $result["msg"] = Lang::get('agreements.edit_success');
                            }
                        }
                        //}
                    } else {
                        $result["response"] = "error";
                        $result["msg"] = Lang::get('agreements.valid_upto_date_error');


                    }
                } else {
                    $result["response"] = "error";
                    $result["msg"] = Lang::get('agreements.manual_end_date_contract_error');
                }
            } else {
                $result["response"] = "error";
                $result["msg"] = Lang::get('agreements.manual_end_date_logs_error');
            }
        } else {
            $result["response"] = "error";
            $result["msg"] = Lang::get('agreements.start_date_end_date_error');
        }

        UpdatePendingPaymentCount::dispatch($agreement->hospital_id);
        return $result;
    }

    public static function renewAgreement($agreement, $hospital)
    {

        $result = array();
        $emailCheck = Request::input('emailCheck');
        $agreement->name = Request::input('name');

        $start_date = mysql_date(Request::input('start_date'));
        $end_date = mysql_date(Request::input('end_date'));
        $hospital_details = Hospital::findOrFail($agreement->hospital_id);
        /*condition for start date & end date is added, i.e. start date should be always less than or equal to end date
            on 22 April 2016 */
        if ($start_date <= $end_date) {
            $valid_upto = mysql_date(Request::input('valid_upto'));
            if ($valid_upto == "0000-00-00") {
                $valid_upto = $end_date;
            }
            /*condition for valid_upto date is added i.e. valid_upto date should not be less than end date &
            should not be greater than 90 days from end date
            on 20 April 2016 */
            $date_after90_days_of_end_date = date('Y-m-d', strtotime('+90 day', strtotime($end_date)));

            if (($end_date <= $valid_upto) && ($valid_upto <= $date_after90_days_of_end_date)) {
                $agreement->archived = true;
                $agreement->save();

                $new_agreement = new Agreement;
                $new_agreement->hospital_id = $hospital->id;
                $new_agreement->name = Request::input('name');
                $new_agreement->start_date = mysql_date(Request::input('start_date'));
                $new_agreement->end_date = mysql_date(Request::input('end_date'));
                $new_agreement->valid_upto = mysql_date(Request::input('valid_upto'));
                $new_agreement->payment_frequency_type = $agreement->payment_frequency_type;
                $new_agreement->payment_frequency_start_date = mysql_date(Request::input('frequency_start_date'));
                $new_agreement->archived = false;
                if (Request::has("on_off")) {
                    if (Request::input("on_off") == 1) {
                        $new_agreement->approval_process = Request::input('on_off');
                    } else {
                        //$new_agreement->approval_process = 0;
                        $new_agreement->approval_process = Request::input('on_off');
                    }
                } else {
                    $new_agreement->approval_process = 0;
                }
                if ($hospital_details->invoice_dashboard_on_off == 1) {
                    $new_agreement->send_invoice_day = Request::input('send_invoice_reminder_day');
                    $new_agreement->invoice_reminder_recipient_1 = Request::input('invoice_reminder_recipient1');
                    $new_agreement->invoice_reminder_recipient_2 = Request::input('invoice_reminder_recipient2');
                    $new_agreement->invoice_reminder_recipient_1_opt_in_email = Request::has('emailCheck_recipient_1') ? 1 : 0;
                    $new_agreement->invoice_reminder_recipient_2_opt_in_email = Request::has('emailCheck_recipient_2') ? 1 : 0;
                    $new_agreement->invoice_receipient = Request::input('invoice_receipient1') . ',' . Request::input('invoice_receipient2') . ',' . Request::input('invoice_receipient3');
                } else {
                    $new_agreement->send_invoice_day = '0';
                    $new_agreement->invoice_reminder_recipient_1 = "";
                    $new_agreement->invoice_reminder_recipient_2 = "";
                    $new_agreement->invoice_reminder_recipient_1_opt_in_email = '0';
                    $new_agreement->invoice_reminder_recipient_2_opt_in_email = '0';
                    $new_agreement->invoice_receipient = "";
                }
                if (!$new_agreement->save()) {
                    $result["response"] = "error";
                    $result["msg"] = Lang::get('agreements.renew_error');
                } else //While saving newly created agreement
                {
                    $result = $new_agreement->add_agreement_approval_managers($new_agreement);
                }//End of while creating new agreement loop
                foreach ($agreement->contracts as $contract) {
                    $contract->archived = true;
                    $contract->save();

                    $new_contract = new Contract;
                    $new_contract->agreement_id = $new_agreement->id;
                    $new_contract->physician_id = $contract->physician_id;
                    $new_contract->contract_type_id = $contract->contract_type_id;
                    $new_contract->payment_type_id = $contract->payment_type_id;
                    $new_contract->contract_name_id = $contract->contract_name_id;
                    $new_contract->min_hours = $contract->min_hours;
                    $new_contract->max_hours = $contract->max_hours;
                    $new_contract->expected_hours = $contract->expected_hours;
                    $new_contract->annual_cap = $contract->annual_cap;/*annual cap*/
                    $new_contract->rate = $contract->rate;
                    $new_contract->weekday_rate = $contract->weekday_rate;
                    $new_contract->weekend_rate = $contract->weekend_rate;
                    $new_contract->holiday_rate = $contract->holiday_rate;
                    $new_contract->on_call_rate = $contract->on_call_rate;
                    $new_contract->called_back_rate = $contract->called_back_rate;
                    $new_contract->called_in_rate = $contract->called_in_rate;
                    $new_contract->burden_of_call = $contract->burden_of_call;
                    $new_contract->description = $contract->description;
                    $new_contract->archived = false;
                    $new_contract->physician_opt_in_email = $contract->physician_opt_in_email;
                    $new_contract->default_to_agreement = $contract->default_to_agreement;
                    $new_contract->is_lawson_interfaced = $contract->is_lawson_interfaced;
                    $new_contract->manual_contract_end_date = $new_agreement->end_date;
                    $new_contract->manual_contract_valid_upto = $new_agreement->valid_upto;
                    $physician_practice = PhysicianPractices::where('hospital_id', '=', $hospital->id)
                        ->where('physician_id', '=', $contract->physician_id)
                        ->whereRaw("start_date <= now()")
                        ->whereRaw("end_date >= now()")
                        ->whereNull("deleted_at")
                        ->orderBy("start_date", "desc")
                        ->first();

                    $new_contract->practice_id = $physician_practice->practice_id;
                    $new_contract->on_call_process = $contract->on_call_process; // accept zero rate for per_diem contract by 1254 : set on call process flag rate zero
                    $new_contract->holiday_on_off = $contract->holiday_on_off;
                    // call-coverage-duration  by 1254 : added partial hours
                    $new_contract->partial_hours = $contract->partial_hours;
                    $new_contract->allow_max_hours = $contract->allow_max_hours;
                    $new_contract->save();

                    $new_contract->manual_contract_end_date = $new_contract->manual_contract_end_date != NULL ? $new_contract->manual_contract_end_date : $new_agreement->end_date;
                    if ($new_contract->payment_type_id == PaymentType::PER_DIEM) {
                        $new_contractOnCallRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->on_call_rate, ContractRate::ON_CALL_RATE);
                        $new_contractCalledBackRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->called_back_rate, ContractRate::CALLED_BACK_RATE);
                        $new_contractCalledInRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->called_in_rate, ContractRate::CALLED_IN_RATE);
                        $new_contractWeekdayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->weekday_rate, ContractRate::WEEKDAY_RATE);
                        $new_contractWeekendRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->weekend_rate, ContractRate::WEEKEND_RATE);
                        $new_contractHolidayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->holiday_rate, ContractRate::HOLIDAY_RATE);
                    } else if ($new_contract->payment_type_id == PaymentType::PSA) {
                    } else {
                        $new_contractFMVRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->rate, ContractRate::FMV_RATE);
                    }

                    foreach ($contract->manager_info() as $managers) {
                        $new_managers = new ApprovalManagerInfo;
                        $new_managers->agreement_id = $new_contract->agreement_id;
                        $new_managers->level = $managers->level;
                        $new_managers->type_id = $managers->type_id;
                        $new_managers->user_id = $managers->user_id;
                        $new_managers->initial_review_day = $managers->initial_review_day;
                        $new_managers->final_review_day = $managers->final_review_day;
                        $new_managers->opt_in_email_status = $managers->opt_in_email_status;
                        $new_managers->is_deleted = '0';
                        $new_managers->contract_id = $new_contract->id;

                        $new_managers->save();
                    }
                    foreach ($contract->actions as $action) {
                        $new_contract->actions()->attach($action->id, ['hours' => $action->pivot->hours]);
                    }
                    if ($contract->is_lawson_interfaced) {
                        $contract_interface_lawson_apcdistrib = ContractInterfaceLawsonApcdistrib::where('contract_id', '=', $contract->id)->first();
                        $new_contract_interface_lawson_apcdistrib = new ContractInterfaceLawsonApcdistrib;
                        $new_contract_interface_lawson_apcdistrib->contract_id = $new_contract->id;
                        $new_contract_interface_lawson_apcdistrib->cvd_company = $contract_interface_lawson_apcdistrib->cvd_company;
                        $new_contract_interface_lawson_apcdistrib->cvd_vendor = $contract_interface_lawson_apcdistrib->cvd_vendor;
                        $new_contract_interface_lawson_apcdistrib->cvd_dist_company = $contract_interface_lawson_apcdistrib->cvd_dist_company;
                        $new_contract_interface_lawson_apcdistrib->cvd_dis_acct_unit = $contract_interface_lawson_apcdistrib->cvd_dis_acct_unit;
                        $new_contract_interface_lawson_apcdistrib->cvd_dis_account = $contract_interface_lawson_apcdistrib->cvd_dis_account;
                        $new_contract_interface_lawson_apcdistrib->cvd_dis_sub_acct = $contract_interface_lawson_apcdistrib->cvd_dis_sub_acct;
                        $new_contract_interface_lawson_apcdistrib->created_by = Auth::user()->id;
                        $new_contract_interface_lawson_apcdistrib->updated_by = Auth::user()->id;
                        $new_contract_interface_lawson_apcdistrib->save();
                    }
                    $invoice_notes = InvoiceNote::where("note_for", "=", $contract->id)
                        ->where('hospital_id', '=', $hospital->id)
                        ->where('is_active', '=', true)
                        ->get();
                    foreach ($invoice_notes as $invoice_note) {
                        $new_invoice_note = $invoice_note->replicate();
                        $new_invoice_note->note_for = $new_contract->id;
                        $new_invoice_note->save();
                    }

                }

                $result["response"] = "success";
                $result['new_agreemnet_id'] = $new_agreement->id;
                $result["msg"] = Lang::get('agreements.renew_success');
            } else {
                $result["response"] = "error";
                $result["msg"] = Lang::get('agreements.valid_upto_date_error');

            }
        } else {
            $result["response"] = "error";
            $result["msg"] = Lang::get('agreements.start_date_end_date_error');
        }
        return $result;
    }

    /*
     * php delete function that deals with directories recursively
     */

    public function add_agreement_approval_managers($agreement)
    {
        //Write code for approval manager conditions & to save them in database
        $emailCheck = Request::input('emailCheck');
        $agreement_id = $agreement->id;//fetch agreement id
        $approval_manager_info = array();
        $approval_level = array();
        $levelcount = 0;
        $contract_id = 0;
        //Fetch all levels of approval managers & remove NA approvaal levels
        for ($i = 1; $i < 7; $i++) {
            if (Request::input('approval_manager_level' . $i) != 0) {

                $approval_level[$levelcount]['approvalType'] = 0;
                $approval_level[$levelcount]['level'] = $levelcount + 1;
                $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                $approval_level[$levelcount]['emailCheck'] = $emailCheck > 0 ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                $levelcount++;
            }
        }
        // asort($approval_level);//Sorting on basis of type of approval level
        $approval_level_number = 1;
        $fail_to_save_level = 0;
        foreach ($approval_level as $key => $approval_level) {
            // code...
            $agreement_approval_manager_info = new ApprovalManagerInfo;
            $agreement_approval_manager_info->agreement_id = $agreement_id;
            $agreement_approval_manager_info->contract_id = $contract_id;
            $agreement_approval_manager_info->level = $approval_level['level'];
            $agreement_approval_manager_info->type_id = $approval_level['approvalType'];
            $agreement_approval_manager_info->user_id = $approval_level['approvalManager'];
            $agreement_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
            $agreement_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
            $agreement_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
            $agreement_approval_manager_info->is_deleted = '0';

            if (!$agreement_approval_manager_info->save()) {
                $fail_to_save_level = 1;
            } else {
                //success
                $approval_level_number++;
            }
        }//End of for loop
        if ($fail_to_save_level == 1) {
            //if fails while saving approval level, delete all the approval levels & agreement as well
            //Delete all the entries from approval levels for the agreement
            DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)->forceDelete();
            DB::table('agreements')->where('id', "=", $agreement_id)->delete();
            $result["response"] = "error";
            $result["msg"] = Lang::get('hospitals.create_agreement_error');
        } else {
            $result["response"] = "success";
            $result["msg"] = Lang::get('hospitals.create_agreement_success');
        }
        return $result;
    }

    public static function copyAgreement($agreement, $hospital)
    {
        $result = array();
        $new_agreement = new Agreement;
        $new_agreement->hospital_id = $hospital->id;
        $new_agreement->name = $agreement->name;
        $new_agreement->start_date = $agreement->start_date;
        $new_agreement->end_date = $agreement->end_date;
        if ($agreement->valid_upto == "0000-00-00") {
            $new_agreement->valid_upto = $new_agreement->end_date;
        } else {
            $new_agreement->valid_upto = $agreement->valid_upto;
        }
        $new_agreement->archived = false;
        $new_agreement->approval_process = $agreement->approval_process;
        $new_agreement->send_invoice_day = $agreement->send_invoice_day;
        $new_agreement->pass1_day = $agreement->pass1_day;
        $new_agreement->pass2_day = $agreement->pass2_day;
        $new_agreement->invoice_reminder_recipient_1 = $agreement->invoice_reminder_recipient_1;
        $new_agreement->invoice_reminder_recipient_2 = $agreement->invoice_reminder_recipient_2;
        $new_agreement->invoice_reminder_recipient_1_opt_in_email = $agreement->invoice_reminder_recipient_1_opt_in_email;
        $new_agreement->invoice_reminder_recipient_2_opt_in_email = $agreement->invoice_reminder_recipient_2_opt_in_email;

        $new_agreement->invoice_receipient = $agreement->invoice_receipient;
        $new_agreement->payment_frequency_type = $agreement->payment_frequency_type;
        $new_agreement->payment_frequency_start_date = $agreement->payment_frequency_start_date;

        if (!$new_agreement->save()) {
            return Redirect::back()->with([
                'error' => Lang::get('agreements.renew_error')
            ]);
        } else //While saving newly created agreement
        {

            $agreement_manager_info = DB::table('agreement_approval_managers_info')->where('is_deleted', '=', '0')
                ->where('agreement_id', '=', $agreement->id)
                ->where('contract_id', '=', '0')
                ->get();
            foreach ($agreement_manager_info as $managers) {
                // code...
                $new_managers = new ApprovalManagerInfo;
                $new_managers->agreement_id = $new_agreement->id;
                $new_managers->level = $managers->level;
                $new_managers->type_id = $managers->type_id;
                $new_managers->user_id = $managers->user_id;
                $new_managers->initial_review_day = $managers->initial_review_day;
                $new_managers->final_review_day = $managers->final_review_day;
                $new_managers->opt_in_email_status = $managers->opt_in_email_status;
                $new_managers->is_deleted = '0';
                $new_managers->contract_id = 0;
                $new_managers->save();
            }

        }//End of while creating new agreement loop

        foreach ($agreement->contracts as $contract) {

            $new_contract = new Contract;
            $new_contract->agreement_id = $new_agreement->id;
            $new_contract->physician_id = $contract->physician_id;
            $new_contract->contract_type_id = $contract->contract_type_id;
            $new_contract->payment_type_id = $contract->payment_type_id;
            $new_contract->contract_name_id = $contract->contract_name_id;
            $new_contract->min_hours = $contract->min_hours;
            $new_contract->max_hours = $contract->max_hours;
            $new_contract->expected_hours = $contract->expected_hours;
            $new_contract->annual_cap = $contract->annual_cap;/*annual cap*/
            $new_contract->rate = $contract->rate;
            $new_contract->weekday_rate = $contract->weekday_rate;
            $new_contract->weekend_rate = $contract->weekend_rate;
            $new_contract->holiday_rate = $contract->holiday_rate;
            $new_contract->on_call_rate = $contract->on_call_rate;
            $new_contract->called_back_rate = $contract->called_back_rate;
            $new_contract->called_in_rate = $contract->called_in_rate;
            $new_contract->burden_of_call = $contract->burden_of_call;
            $new_contract->description = $contract->description;
            $new_contract->archived = false;
            $new_contract->physician_opt_in_email = $contract->physician_opt_in_email;
            $new_contract->default_to_agreement = $contract->default_to_agreement;
            $physician_practice = PhysicianPractices::where('hospital_id', '=', $hospital->id)->where('physician_id', '=', $contract->physician_id)->whereNull("deleted_at")->orderBy("start_date", "desc")->first();
            $new_contract->practice_id = $physician_practice->practice_id;
            $new_contract->on_call_process = $contract->on_call_process;
            $new_contract->holiday_on_off = $contract->holiday_on_off;
            $new_contract->allow_max_hours = $contract->allow_max_hours;
            $new_contract->manual_contract_end_date = $contract->manual_contract_end_date;
            $new_contract->manual_contract_valid_upto = $contract->manual_contract_valid_upto;
            $new_contract->partial_hours = $contract->partial_hours;
            if ($new_contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $new_contract->partial_hours_calculation = $contract->partial_hours_calculation;
            }
            $new_contract->state_attestations_monthly = $contract->state_attestations_monthly;
            $new_contract->state_attestations_annually = $contract->state_attestations_annually;
            $new_contract->receipient1 = $contract->receipient1;
            $new_contract->receipient2 = $contract->receipient2;
            $new_contract->receipient3 = $contract->receipient3;
            $new_contract->supervision_type = $contract->supervision_type;
            $new_contract->save();

            $invoice_notes_contract = InvoiceNote::getInvoiceNotes($contract->id, InvoiceNote::CONTRACT, $hospital->id, 0);

            if (count($invoice_notes_contract) > 0) {
                foreach ($invoice_notes_contract as $note_index => $note) {
                    $invoice_note = new InvoiceNote();
                    $invoice_note->note_type = InvoiceNote::CONTRACT;
                    $invoice_note->note_for = $new_contract->id;
                    $invoice_note->note_index = $note_index;
                    $invoice_note->note = $note;
                    $invoice_note->is_active = true;
                    $invoice_note->save();
                }
            }

            $new_contract->manual_contract_end_date = $new_contract->manual_contract_end_date != NULL ? $new_contract->manual_contract_end_date : $new_agreement->end_date;
            if ($new_contract->payment_type_id == PaymentType::PER_DIEM) {
                $new_contractOnCallRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->on_call_rate, ContractRate::ON_CALL_RATE);
                $new_contractCalledBackRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->called_back_rate, ContractRate::CALLED_BACK_RATE);
                $new_contractCalledInRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->called_in_rate, ContractRate::CALLED_IN_RATE);
                $new_contractWeekdayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->weekday_rate, ContractRate::WEEKDAY_RATE);
                $new_contractWeekendRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->weekend_rate, ContractRate::WEEKEND_RATE);
                $new_contractHolidayRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->holiday_rate, ContractRate::HOLIDAY_RATE);
            } else if ($new_contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

                $contract_rate_with_range = ContractRate::where('contract_id', '=', $contract->id)
                    ->where('effective_start_date', '>=', @mysql_date($new_agreement->start_date))
                    ->where('effective_end_date', '<=', @mysql_date($new_contract->manual_contract_end_date))
                    ->where("status", "=", '1')
                    ->where("rate_type", "=", 8)
                    ->get();
                foreach ($contract_rate_with_range as $contract_rate_obj) {
                    $new_contract_rate = new ContractRate();

                    $new_contract_rate->rate = $contract_rate_obj->rate;
                    $new_contract_rate->effective_start_date = $contract_rate_obj->effective_start_date;
                    $new_contract_rate->effective_end_date = $contract_rate_obj->effective_end_date;
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
                $new_contractFMVRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->rate, ContractRate::MONTHLY_STIPEND_RATE);
            } else {
                $new_contractFMVRate = ContractRate::insertContractRate($new_contract->id, $new_agreement->start_date, $new_contract->manual_contract_end_date, $new_contract->rate, ContractRate::FMV_RATE);
            }

            $index = 0;
            foreach ($contract->actions as $action) {
                $new_contract->actions()->attach($action->id, ['hours' => $action->pivot->hours]);
            }

            $sorting_activities = SortingContractActivity::select('*')
                ->where('contract_id', '=', $contract->id)
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

            if ($new_contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_categories_actions = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('is_active', '=', true)
                    ->get();

                if (count($custom_categories_actions) > 0) {
                    foreach ($custom_categories_actions as $custom_categories_action) {
                        $custom_category_action = new CustomCategoryActions();
                        $custom_category_action->contract_id = $contract->id;
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

            $max_sort_order = SortingContractName::select([DB::raw('MAX(sorting_contract_names.sort_order) AS max_sort_order')])
                ->where('sorting_contract_names.practice_id', '=', $new_contract->practice_id)
                ->where('sorting_contract_names.physician_id', '=', $new_contract->physician_id)
                ->where('sorting_contract_names.is_active', '=', 1)
                ->first();

            $sorting_contract = new SortingContractName();
            $sorting_contract->practice_id = $new_contract->practice_id;
            $sorting_contract->physician_id = $new_contract->physician_id;
            $sorting_contract->contract_id = $new_contract->id;
            $sorting_contract->sort_order = $max_sort_order['max_sort_order'] + 1;
            $sorting_contract->save();


            /*add for save on call changed names*/
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                $changed_action_names = OnCallActivity::where("contract_id", "=", $contract->id)->get();
                if (count($changed_action_names) > 0) {
                    foreach ($changed_action_names as $changed_action_name) {
                        $change_new = new OnCallActivity;
                        $change_new->action_id = $changed_action_name->action_id;
                        $change_new->contract_id = $new_contract->id;
                        $change_new->name = $changed_action_name->name;
                        $change_new->save();
                    }
                }
            }
            foreach ($contract->manager_info() as $managers) {
                $new_managers = new ApprovalManagerInfo;
                $new_managers->agreement_id = $new_contract->agreement_id;
                $new_managers->level = $managers->level;
                $new_managers->type_id = $managers->type_id;
                $new_managers->user_id = $managers->user_id;
                $new_managers->initial_review_day = $managers->initial_review_day;
                $new_managers->final_review_day = $managers->final_review_day;
                $new_managers->opt_in_email_status = $managers->opt_in_email_status;
                $new_managers->is_deleted = '0';
                $new_managers->contract_id = $new_contract->id;
                $new_managers->save();
            }

            // Copy invoice notes for contract added by akash
            $invoice_notes = InvoiceNote::where("note_for", "=", $contract->id)
                ->where('is_active', '=', true)
                ->get();
            foreach ($invoice_notes as $invoice_note) {
                $new_invoice_note = $invoice_note->replicate();
                $new_invoice_note->note_for = $new_contract->id;
                $new_invoice_note->save();
            }
            // End Copy invoice notes for contract added by akash
        }

        $result["response"] = "success";
        $result["new_agreement_id"] = $new_agreement->id;
        $result["msg"] = Lang::get('agreements.copy_success');

        return $result;

    }

    //For Update agreement

    public static function listByPhysician($physician)
    {
        if ($physician instanceof Physician) {
            $physician = $physician->id;
        }

        /*where clause of is_deleted is added for soft delete of agreement*/
        $query = self::select("agreements.id as id", "agreements.name as name")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->join("physicians", "physicians.id", "=", "contracts.physician_id")
            ->where("agreements.is_deleted", "=", "0")
            ->where("physicians.id", "=", $physician)
            ->where("contracts.end_date", "=", "0000-00-00 00:00:00")
            ->orderBy("agreements.name");
        if (count($query->pluck("name", "id")) > 0) {
            return $query->pluck("name", "id");
        } else {
            return 0;
        }
    }
    /*End of update agreement function */

//For Renew agreement

    public static function getAllPhysician($hospital_id)
    {
        $query = Physician::select(DB::raw("distinct(physicians.id) as id"))
            ->addSelect("physicians.first_name")
            ->addSelect("physicians.last_name")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->where("agreements.hospital_id", "=", $hospital_id)
            ->orderBy('physicians.last_name', 'asc')
            ->get();
        return $query;
    }
    /*End of renew agreement function */
    /*Copy agreement start*/

    public static function getActiveAgreementData()
    {
        /*below where clause of is_deleted is added for soft delete
            Code modified_on: 08/04/2016
            */
        $agreements = self::whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.end_date >= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->get();

        $results = [];
        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementData($agreement->id);
        }
        return $results;
    }

    /*Copy agreement end*/

    public static function getAgreementData($agreement)
    {
        $now = new DateTime('now');

        if (is_a($agreement, 'Illuminate\Database\Eloquent\Collection')) {
            $agreement = $agreement->all();
            $agreement = $agreement[0];
        }

        if (!($agreement instanceof Agreement)) {
            $agreement = self::findOrFail($agreement);
        }

        $agreement_data = new StdClass;
        $agreement_data->id = $agreement->id;
        $agreement_data->hospital_id = $agreement->hospital_id;
        $agreement_data->name = $agreement->name;
        $agreement_data->start_date = format_date($agreement->start_date);
        $agreement_data->end_date = format_date($agreement->end_date);
        $agreement_data->payment_frequency_start_date = format_date($agreement->payment_frequency_start_date);
        $agreement_data->payment_frequency_type = $agreement->payment_frequency_type;
        if ($agreement->valid_upto == "0000-00-00") {
            $valid_upto = $agreement_data->end_date;
        } else {
            $valid_upto = $agreement->valid_upto;
        }
        $agreement_data->term = months($agreement->start_date, $valid_upto);
        $agreement_data->months = [];
        $agreement_data->start_dates = [];
        $agreement_data->end_dates = [];
        $agreement_data->dates = [];
        $agreement_data->current_month = -1;


        $start_date = with(new DateTime($agreement->payment_frequency_start_date))->setTime(0, 0, 0);

        // Below changes are done based on payment frequency of agreement by akash.
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
        $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

        foreach ($payment_frequency_range as $index => $date_obj) {
            $start_date = date("m/d/Y", strtotime($date_obj['start_date']));
            $end_date = date("m/d/Y", strtotime($date_obj['end_date']));
            $period_data = new StdClass;
            $period_data->number = $index + 1;
            $period_data->start_date = $start_date;
            $period_data->end_date = $end_date;
            $period_data->now_date = $now->format('m/d/Y');
            $period_data->current = ($now->format('m/d/Y') >= $start_date && $now->format('m/d/Y') <= $end_date);

            if ($period_data->current) {
                $agreement_data->current_month = $period_data->number;
            }

            $agreement_data->months[$period_data->number] = $period_data;
            $agreement_data->start_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date}";
            $agreement_data->end_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->end_date}";
            $agreement_data->dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date} - {$period_data->end_date}";
        }

        if ($agreement_data->current_month == -1) {
            foreach ($agreement_data->end_dates as $key => $value) {
                if (strpos($value, format_date($valid_upto))) {
                    $agreement_data->current_month = $key;

                } else {
                    $agreement_data->current_month = $key;
                }
            }
        }
        return $agreement_data;
    }

    public static function getAllAgreementData()
    {
        /*below where clause of is_deleted is added for soft delete
         Code modified_on: 08/04/2016
         */
        $agreements = self::whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->get();

        $results = [];
        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementData($agreement->id);
        }

        return $results;
    }

    public static function getAllActiveAgreementApprovalData($type)
    {
        //new approval levels added 28Aug2018
        $users_ids = ApprovalManagerInfo::join('agreements', 'agreements.id', '=', 'agreement_approval_managers_info.agreement_id')
            ->where(function ($pass) {
                $pass->where('agreement_approval_managers_info.initial_review_day', '=', date("d"))
                    ->orWhere('agreement_approval_managers_info.final_review_day', '=', date("d"));
            })
            ->where("agreement_approval_managers_info.type_id", "=", $type - 1) // -1 for remove physician type 30Aug2018
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw("agreements.approval_process = '1'")
            ->where(function ($end) {
                $end->whereRaw('agreements.end_date >= NOW()')
                    ->orWhereRaw('agreements.valid_upto >= NOW()');
            })
            ->distinct()
            ->pluck("agreement_approval_managers_info.user_id");


        $results = [];
        $LogApproval = new LogApproval();
        $contracts = new Contract();
        $i = 0;
        foreach ($users_ids as $user_id) {
            $contracts = $LogApproval->logsForApproval($user_id, $type - 1, 0, 0);
            if (count($contracts) > 0) {
                $results[$user_id]["contracts"] = $LogApproval->logsForApproval($user_id, $type - 1, 0, 0);
            }

        }
        return $results;
    }

    public static function getHospitalAgreementData($hospitalId, $contractTypeId = -1, $show_archived_flag = 0)
    {
        /*below where clause of is_deleted is added for soft delete
            Code modified_on: 08/04/2016
            */

        $query = self::where('hospital_id', '=', $hospitalId)
            ->select("agreements.*")
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc');

        if ($show_archived_flag == 0) {
            $query->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()');
        }

        if ($contractTypeId != -1) {
            $query->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        }

        return self::getAgreementsData($query->get());
    }

    private static function getAgreementsData($agreements)
    {
        $results = [];

        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementData($agreement->id);
        }

        return $results;
    }

    public static function getHospitalAgreementDataForReports($hospitalId, $contractTypeId = -1, $show_archived_flag = 0)
    {
        /*below where clause of is_deleted is added for soft delete
            Code modified_on: 07/04/2016
            */

        $query = self::where('hospital_id', '=', $hospitalId)
            ->select("agreements.*")
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc');

        if ($show_archived_flag == 0) {
            $query->whereRaw('agreements.archived = 0');
        }

        if ($contractTypeId != -1) {
            $query->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        }

        return self::getAgreementsDataForReports($query->get());
    }

    private static function getAgreementsDataForReports($agreements)
    {
        $results = [];

        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementDataForReport($agreement->id);
        }
        return $results;
    }

    public static function getAgreementDataForReport($agreement)
    {
        $now = new DateTime('now');

        if (is_a($agreement, 'Illuminate\Database\Eloquent\Collection')) {
            $agreement = $agreement->all();
            $agreement = $agreement[0];
        }

        if (!($agreement instanceof Agreement)) {
            $agreement = self::findOrFail($agreement);
        }

        $agreement_data = new StdClass;
        $agreement_data->id = $agreement->id;
        $agreement_data->hospital_id = $agreement->hospital_id;
        $agreement_data->name = $agreement->name;
        $agreement_data->start_date = format_date($agreement->start_date);
        $agreement_data->end_date = format_date($agreement->end_date);
        $agreement_data->term = months($agreement->start_date, $agreement->end_date);
        $agreement_data->months = [];
        $agreement_data->start_dates = [];
        $agreement_data->end_dates = [];
        $agreement_data->dates = [];
        $agreement_data->current_month = -1;
        $agreement_data->disable = false;

        // Below changes are done based on payment frequency of agreement by akash.
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
        $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

        foreach ($payment_frequency_range as $index => $date_obj) {
            $start_date = date("m/d/Y", strtotime($date_obj['start_date']));
            $end_date = date("m/d/Y", strtotime($date_obj['end_date']));
            $month_data = new StdClass;
            $month_data->number = $index + 1;
            $month_data->start_date = $start_date;
            $month_data->end_date = $end_date;
            $month_data->now_date = $now->format('m/d/Y');
            $month_data->current = ($now->format('m/d/Y') >= $start_date && $now->format('m/d/Y') <= $end_date);

            if ($month_data->current) {
                $agreement_data->current_month = $month_data->number;
            }

            $agreement_data->months[$month_data->number] = $month_data;
            $agreement_data->start_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date}";
            $agreement_data->end_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->end_date}";
            $agreement_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
        }


        return $agreement_data;
    }

    public static function getPracticeAgreementData($practice_id, $contractTypeId = -1)
    {
        /*below where clause of is_deleted is added for soft delete
           Code modified_on: 07/04/2016
           */
        $ids = array();
        $results = new stdClass();
        $key = 0;

        $query = self::select('agreements.*')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_contracts.physician_id')
            ->join("practices", function ($join) {
                $join->on("physician_practice_history.practice_id", "=", "practices.id")
                    ->on("agreements.hospital_id", "=", "practices.hospital_id");
            })
            ->where('practices.id', '=', $practice_id)
            ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy('agreements.id');

        if ($contractTypeId != -1) {
            $query->where("contracts.contract_type_id", "=", $contractTypeId);
        }
        $result = $query->get();
        foreach ($result as $k => $value) {
            $results->$key = $value;
            $ids[] = $value->id;
            $key++;
        }

        return self::getAgreementsDataForReports($results);
    }

    public static function getPhysicianAgreementData($physician, $contractTypeId = -1, $practice_id)
    {
        /*below where clause of is_deleted is added for soft delete
          Code modified_on: 07/04/2016
          */

        //   issue fixed : all agreement showing under practices for one to many by 1254

        $physician_id = $physician->id;
        $practice_id = $practice_id;
        //drop column practice_id from table 'physicians' changes by 1254
        $query = $agreements = self::select('agreements.*')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('physician_practices', 'physician_practices.hospital_id', '=', 'agreements.hospital_id')
            ->where('physician_practices.practice_id', '=', $practice_id)
            ->where('physicians.id', '=', $physician_id)
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('contracts.end_date = "0000-00-00 00:00:00"')
            ->whereRaw('DATE_ADD(agreements.end_date, INTERVAL 90 DAY) >= NOW()')
            ->groupBy('agreements.id');

        if ($contractTypeId != -1) {
            $query->where("contracts.contract_type_id", "=", $contractTypeId);
        }

        return self::getAgreementsData($query->get());
    }

    public static function getActiveAgreement($hospital_id)
    {
        $agreements = self::where('agreements.hospital_id', '=', $hospital_id)
            ->whereRaw('agreements.start_date <= NOW()')
            ->where(function ($query) {
                $query->whereRaw('agreements.end_date >= NOW()')
                    ->orWhereRaw('agreements.valid_upto >= NOW()');
            })
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->get();

        $results = [];
        foreach ($agreements as $agreement) {
            $results[] = $agreement;
        }
        return $results;
    }

    public static function getAllApproveLogsAgreement()
    {
        $result = [];
        $query = self::select("agreements.id as agreement_id", DB::raw("MONTH(physician_logs. date) as month"), DB::raw("YEAR(physician_logs. date) as year"), "contracts.contract_type_id as contract_type")
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('physician_logs', 'physician_logs.contract_id', '=', 'contracts.id')
            ->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
            ->where('agreements.send_invoice_day', '=', date("d"))
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw("agreements.approval_process = '1'")
            ->whereRaw('agreements.start_date <= NOW()')
            ->where('log_approval.role', '=', LogApproval::financial_manager)
            ->where('log_approval.approval_status', '=', 1)
            ->whereBetween('log_approval.approval_date', [mysql_date(date("m/01/Y")), mysql_date(date("m/t/Y"))])
            ->orderBy("agreements.id")
            ->orderBy(DB::raw("MONTH(physician_logs. date)"))
            ->orderBy(DB::raw("YEAR(physician_logs. date)"))
            ->distinct()
            ->get();

        $amount_paid = new Amount_paid();
        $physician_logs = new PhysicianLog();

        foreach ($query as $agreement_details) {
            $agreements = [];
            $agreements[] = $agreement_details->agreement_id;
            $practices_ids = [];
            $physicians_ids = [];
            $practices = Practice::listByAgreements($agreements, $agreement_details->contract_type);
            $physicians = DB::table("physicians")->select(
                DB::raw("physicians.id as physician_id")
            )
                ->join("contracts", "contracts.physician_id", "=", "physicians.id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->whereIn("contracts.agreement_id", $agreements)
                ->orderBy("physicians.last_name", "asc")
                ->orderBy("physicians.first_name", "asc")
                ->where("contracts.contract_type_id", "=", $agreement_details->contract_type)->get();
            foreach ($practices as $practice_id => $name) {
                $practices_ids[] = $practice_id;
            }
            foreach ($physicians as $physician) {
                $physicians_ids[] = $physician->physician_id;
            }
            $agreement_dates = self::getAgreementData($agreement_details->agreement_id);
            foreach ($agreement_dates->dates as $num => $date) {
                $start_date = $agreement_dates->start_dates[$num];
                $start_date = explode(":", $start_date);
                $end_date = $agreement_dates->end_dates[$num];
                $end_date = explode(":", $end_date);
                $start_month = date("m", strtotime($start_date[1]));
                $end_month = date("m", strtotime($end_date[1]));
                if ($start_month == $agreement_details->month || $end_month == $agreement_details->month) {
                    $logs = PhysicianLog::select("physician_logs.*")
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->where('agreements.id', '=', $agreement_details->agreement_id)
                        ->where('contracts.contract_type_id', '=', $agreement_details->contract_type)
                        ->where('log_approval.role', '=', LogApproval::financial_manager)
                        ->where('log_approval.approval_status', '=', 1)
                        ->whereBetween('log_approval.approval_date', [mysql_date(date("m/01/Y")), mysql_date(date("m/t/Y"))])
                        ->whereBetween('physician_logs.date', [mysql_date($start_date[1]), mysql_date($end_date[1])])
                        ->get();
                    if (count($logs) > 0) {
                        $physician_ids = $physicians_ids;
                        $practice_ids = $practices_ids;
                        $months_start = [];
                        $months_end = [];
                        $months = [];
                        $agreement = Agreement::findOrFail($agreement_details->agreement_id);
                        $hospital = Hospital::findOrFail($agreement->hospital_id);
                        $recipient = explode(',', $agreement->invoice_receipient);
                        $months_start[] = $num;
                        $months_end[] = $num;
                        $months[] = $num;
                        $months[] = $num;
                        $approved_logs = $physician_logs->logReportData($hospital, $agreements, $physician_ids, $months_start, $months_end, $agreement_details->contract_type);
                        $paid_data = $amount_paid->invoiceReportData($hospital, $agreements, $practice_ids, $months_start, $months_end, $agreement_details->contract_type, false, $payedcontracts = array());
                        $agreement_ids = implode(',', $agreements);
                        $practice_ids = implode(',', array_unique($practice_ids));
                        $physician_ids = implode(',', array_unique($physician_ids));
                        $months = implode(',', $months);
                        $result[] = [
                            'hospital' => $hospital,
                            'contract_type' => $agreement_details->contract_type,
                            'practices' => $practice_ids,
                            'physicians' => $physician_ids,
                            'agreements' => $agreement_ids,
                            'recipient' => array_filter($recipient),
                            'months' => $months,
                            'start_date' => $start_date[1],
                            'approved_logs' => $approved_logs,
                            'paid_data' => $paid_data
                        ];
                    }
                }
            }
        }

        return $result;
    }

    public static function getAgreementStartDateForYear($id)
    {
        $result = array();
        $agreement = self::findOrFail($id);
        $agreement_start_date = with(new DateTime($agreement->start_date))->setTime(0, 0, 0);
        $agreement_end_date = with(new DateTime($agreement->end_date))->setTime(0, 0, 0);
        $start_date = with(new DateTime($agreement->start_date))->setTime(0, 0, 0);
        $end_date = with(new DateTime($agreement->end_date))->setTime(0, 0, 0);
        $year_start_date = $start_date;
        $now = new DateTime('now');
        $i = 0;
        $add_year_start_date = 0;

        while ($now >= $start_date) {
            $add_year_start_date = $i;
            $start_date = $start_date->modify('+1 year')->setTime(0, 0, 0);
            $i++;
        }
        if ($i > 0) {
            $year_start_date = $agreement_start_date->modify('+' . $add_year_start_date . ' year')->setTime(0, 0, 0);
        }
        $year_end_date = $agreement_end_date->modify('+' . $add_year_start_date . ' year')->setTime(0, 0, 0);
        if ($end_date <= $year_end_date) {
            $end = $end_date;
        } else {
            $end = $year_end_date;
        }
        $result['year_start_date'] = $year_start_date;
        $result['year_end_date'] = $end;
        return $result;
    }

    public static function week_between_two_dates($date1, $date2)
    {
        $first = DateTime::createFromFormat('m/d/Y', $date1);
        $second = DateTime::createFromFormat('m/d/Y', $date2);
        if ($first > $second) {
            SELF::week_between_two_dates($date2, $date1);
        }
        return floor($first->diff($second)->days / 7);
    }

    public static function getApprovalUserAgreements($user_id, $selected_manager, $hospital, $selected_hospital)
    {
        $default = ['0' => 'All'];
        if ($selected_hospital == 0) {
            $hospital_ids = array_keys($hospital);
        } else {
            $hospital_ids[] = $selected_hospital;
        }
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        if ($selected_manager != -1) {
            $agreement = self::select('agreements.id as id', 'agreements.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
                ->where('agreement_approval_managers_info.is_Deleted', '=', '0')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers

                ->where('agreements.is_Deleted', '=', '0')
                ->whereIn('agreements.hospital_id', $hospital_ids)
                ->orderBy('agreements.payment_frequency_type')
                ->distinct()
                ->pluck("name", "id");
        } else if ($selected_manager == -1 && is_practice_manager() && !is_super_hospital_user() && !is_hospital_admin()) {
            $practice_ids = array();
            foreach (Auth::user()->practices as $practice) {
                $practice_ids[] = $practice->id;
            }
            if (count($practice_ids) == 0) {
                $practice_ids[] = 0;
            }
            $agreement = self::select('agreements.id as id', 'agreements.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->join('practices', 'practices.hospital_id', '=', 'hospitals.id')
                ->join('physician_practices', 'physician_practices.practice_id', '=', 'practices.id')
                ->join("contracts", function ($join) {
                    //$join->on("contracts.physician_id", "=", "physicians.id")
                    $join->on("contracts.physician_id", "=", "physician_practices.physician_id")
                        ->on("contracts.agreement_id", "=", "agreements.id");
                })
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->where('agreements.is_Deleted', '=', '0')
                ->whereIn('agreements.hospital_id', $hospital_ids)
                ->whereIn('practices.id', $practice_ids)
                ->orderBy('agreements.payment_frequency_type')
                ->distinct()
                ->pluck("name", "id");
        } else {
            $agreement = self::select('agreements.id as id', 'agreements.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospitals.archived', '=', false)
                ->where('agreements.archived', '=', false)
                ->where('agreements.is_Deleted', '=', '0')
                ->whereIn('agreements.hospital_id', $hospital_ids)
                ->orderBy('agreements.payment_frequency_type')
                ->distinct()
                ->pluck("name", "id");
        }

        $agreement_list = $agreement;
        return $default + $agreement_list->toArray();
    }

    public static function getAgreements($hospital, $selected_hospital)
    {
        $default = ['0' => 'All'];
        if ($selected_hospital == 0) {
            $hospital_ids = array_keys($hospital);
        } else {
            $hospital_ids[] = $selected_hospital;
        }

        $agreement = self::select('agreements.id as id', 'agreements.name as name')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->where('hospitals.archived', '=', false)
            ->where('agreements.archived', '=', false)
            ->where('agreements.is_Deleted', '=', '0')
            ->whereIn('agreements.hospital_id', $hospital_ids)
            ->orderBy('agreements.name')
            ->distinct()
            ->pluck("name", "id");

        $agreement_list = $agreement;
        return $agreement_list->toArray();
    }

    public static function getPaymentRequireInfo($hospital)
    {
        $finalresult = array();
        $default = ['0' => 'All'];
        $deployment_date = getDeploymentDate("ContractRateUpdate");
        $queries = DB::getQueryLog();
        $agreements = self::select('agreements.*')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->where('hospitals.archived', '=', false)
            ->where('agreements.archived', '=', false)
            ->where('agreements.is_Deleted', '=', false)
            ->where('agreements.hospital_id', '=', $hospital)
            ->orderBy('agreements.name')
            ->distinct()->get();
        $last_query = end($queries);

        $agreement_list = array();
        $contract_list = array();
        $agreement_data_list = array();
        $contract_period_list = array();
        $agreement_dates = array();

        try {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $stmt = $pdo->prepare('call sp_hospital_payment_require_info_v6(?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
            $stmt->bindValue((1), $hospital);
            $stmt->bindValue((2), $deployment_date->format('Y-m-d'));
            $exec = $stmt->execute();
            $spresults = [];
            do {
                try {
                    $spresults[] = $stmt->fetchAll(PDO::FETCH_OBJ);
                } catch (\Exception $ex) {

                }
            } while ($stmt->nextRowset());
        } catch (\Exception $e) {
        }

        if (count($spresults) > 0) {
            foreach ($spresults[1] as $result) {

                if ($result->is_remaining_amount_flag == true) {
                    if (!array_key_exists($result->agreement_id, $contract_period_list)) {
                        $contract_period_list[$result->agreement_id] = ["name" => $result->agreement_name, "types" => array()];
                    }
                    if (!array_key_exists($result->payment_type_id, $contract_period_list[$result->agreement_id]["types"])) {
                        $contract_period_list[$result->agreement_id]["types"][$result->payment_type_id] = array();
                    }
                    if (!array_key_exists($result->contract_name_id, $contract_period_list[$result->agreement_id]["types"][$result->payment_type_id])) {

                        $contract_period_list[$result->agreement_id]["types"][$result->payment_type_id][$result->contract_name_id] = ["c_name" => $result->contract_name, "c_months" => array()];
                    }
                    if (!array_key_exists($result->contract_month, $contract_period_list[$result->agreement_id]["types"][$result->payment_type_id][$result->contract_name_id]["c_months"])) {
                        $contract_period_list[$result->agreement_id]["types"][$result->payment_type_id][$result->contract_name_id]["c_months"][$result->contract_month] = date("m/d/Y", strtotime($result->start_date)) . ' - ' . date("m/d/Y", strtotime($result->end_date));
                    }
                } else {

                }
                if (!array_key_exists($result->agreement_id, $agreement_list)) {
                    $agreement_list[$result->agreement_id] = $result->agreement_name;
                }
                //create list of dates for the agreement. There may be duplicates so important to
                //do existence check
                if (!array_key_exists($result->agreement_id, $agreement_dates) || !in_array($result->period_number, array_column($agreement_dates[$result->agreement_id], 'period_number'))) {
                    $date_object = array();
                    $date_object['display_value'] = $result->period_number . ': ' . $result->start_date . ' - ' . $result->end_date;
                    $date_object['start_date'] = $result->start_date;
                    $date_object['end_date'] = $result->end_date;
                    $date_object['start_date'] = $result->start_date;
                    $date_object['period_number'] = $result->period_number;
                    $agreement_dates[$result->agreement_id][$result->period_number] = $date_object;

                }
                //initialize agreeement_id element if not present
                if (!array_key_exists($result->agreement_id, $agreement_data_list)) {
                    $agreement_data_list[$result->agreement_id] = array();

                }

                $contract_key = $result->agreement_id . '_' . $result->payment_type_id . '_' . $result->contract_name_id . '_' . $result->period_number;
                if (!array_key_exists($contract_key, $contract_list)) {
                    $contract_list[$contract_key] = $result->agreement_name . '_' . $result->contract_name . '_' . date("F Y", strtotime($result->start_date));
                }
                $practices = array();
                $physicians = array();
                $payment_types = array();
                $contract_types = array();


                if (!array_key_exists('practices', $agreement_data_list[$result->agreement_id])) {

                    $practices[$result->practice_id] = $result->practice_name;
                    $agreement_data_list[$result->agreement_id]['practices'] = $default + $practices;

                } else {
                    $practices = $agreement_data_list[$result->agreement_id]['practices'];
                    if (!array_key_exists($result->practice_id, $practices)) {
                        $practices[$result->practice_id] = $result->practice_name;
                        $agreement_data_list[$result->agreement_id]['practices'] = $practices;
                    }

                }

                if (!array_key_exists('physician_list', $agreement_data_list[$result->agreement_id])) {
                    $physicians[$result->physician_id] = $result->physician_name;
                    $agreement_data_list[$result->agreement_id]['physician_list'] = $default + $physicians;
                } else {
                    $physicians = $agreement_data_list[$result->agreement_id]['physician_list'];
                    if (!array_key_exists($result->physician_id, $physicians)) {
                        $physicians[$result->physician_id] = $result->physician_name;
                        $agreement_data_list[$result->agreement_id]['physician_list'] = $physicians;
                    }
                }

                if (!array_key_exists('payment_type_list', $agreement_data_list[$result->agreement_id])) {

                    $payment_types[$result->payment_type_id] = $result->payment_type_name;
                    $agreement_data_list[$result->agreement_id]['payment_type_list'] = $default + $payment_types;
                } else {
                    $payment_types = $agreement_data_list[$result->agreement_id]['payment_type_list'];
                    if (!array_key_exists($result->payment_type_id, $payment_types)) {
                        $payment_types[$result->payment_type_id] = $result->payment_type_name;
                        $agreement_data_list[$result->agreement_id]['payment_type_list'] = $payment_types;
                    }
                }

                if (!array_key_exists('contract_type_list', $agreement_data_list[$result->agreement_id])) {

                    $contract_types[$result->contract_type_id] = $result->contract_type_name;
                    $agreement_data_list[$result->agreement_id]['contract_type_list'] = $default + $contract_types;
                } else {
                    $contract_types = $agreement_data_list[$result->agreement_id]['contract_type_list'];
                    if (!array_key_exists($result->contract_type_id, $contract_types)) {
                        $contract_types[$result->contract_type_id] = $result->contract_type_name;
                        $agreement_data_list[$result->agreement_id]['contract_type_list'] = $contract_types;
                    }
                }
                $agreement = self::findOrFail($result->agreement_id);
                $dates = Agreement::getAgreementData($agreement);
                $agreement_data_list[$agreement->id]['current_month'] = $dates->current_month;
                $agreement_data_list[$agreement->id]['dates'] = $dates->dates;
            }
        }


        ksort($contract_list);
        $finalresult['agreement_list'] = $agreement_list;
        $finalresult['contract_list'] = $contract_list;
        $finalresult['agreement_data_list'] = $agreement_data_list;
        $finalresult['contract_period_list'] = $contract_period_list;
        return $finalresult;
    }

    public static function getPaymentRemainingInfo($hospital)
    {
        $result = array();
        $default = ['0' => 'All'];
        $agreements = self::select('agreements.*')
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->where('hospitals.archived', '=', false)
            ->where('agreements.archived', '=', false)
            ->where('agreements.is_Deleted', '=', false)
            ->where('agreements.hospital_id', '=', $hospital)
            ->orderBy('agreements.name')
            ->distinct()->get();

        $agreement_list = array();
        $contract_list = array();
        $agreement_data_list = array();
        foreach ($agreements as $agreement) {
            if (!array_key_exists($agreement->id, $agreement_list)) {
                $agreement_list[$agreement->id] = $agreement->name;
            }
            $practice_list = array();
            $payment_type_list = array();
            $contract_type_list = array();
            $physician_list = array();
            $contracts = self::getContracts($agreement);
            $dates = Agreement::getAgreementData($agreement);
            $number = 0;
            foreach ($dates->start_dates as $contract_month => $start_date) {
                $start_date = explode(':', $start_date);
                $end_date = explode(':', $dates->end_dates[$contract_month]);
                $now = date('m/d/Y');
                if (strtotime($start_date[1]) < strtotime($now)) {
                    foreach ($contracts as $contract_data) {
                        foreach ($contract_data->practices as $practice_data) {
                            if (!array_key_exists($practice_data->id, $practice_list)) {
                                $practice_list[$practice_data->id] = $practice_data->name;
                            }
                            foreach ($practice_data->physicians as $physician_data) {
                                $contract = Contract::findOrFail($physician_data->contract_id);
                                if (!array_key_exists($contract->payment_type_id, $payment_type_list)) {
                                    $payment_type_list[$contract->payment_type_id] = $contract->paymentType->name;
                                }
                                if (!array_key_exists($contract->contract_type_id, $contract_type_list)) {
                                    $contract_type_list[$contract->contract_type_id] = $contract->contractType->name;
                                }
                                if (!array_key_exists($physician_data->id, $physician_list)) {
                                    $physician_list[$physician_data->id] = $physician_data->name;
                                }
                            }
                        }
                    }
                }
            }
            $agreement_data_list[$agreement->id] = ['practices' => $default + $practice_list,
                'payment_type_list' => $default + $payment_type_list,
                'contract_type_list' => $default + $contract_type_list,
                'physician_list' => $default + $physician_list,
                'current_month' => $dates->current_month - 1,
                'dates' => $dates->dates/*$dates_list //commented for shown all in dropdown 15 Nov 2018*/];
            unset($practice_list);
            unset($payment_type_list);
            unset($contract_type_list);
            unset($physician_list);
        }
        ksort($contract_list);
        $result['agreement_list'] = $agreement_list;
        $result['contract_list'] = $contract_list;
        $result['agreement_data_list'] = $agreement_data_list;
        return $result;
    }

    public static function getContracts($agreement)
    {
        $data = [];
        $practice_id = [];

        $contracts = Contract::select('contracts.*')
            ->where('agreement_id', '=', $agreement->id)
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
            ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
            ->whereNull('physicians.deleted_at')
            ->groupBy('contracts.id')
            ->get();

        foreach ($contracts as $contract) {
            $contract_data = new StdClass();
            $contract_data->id = $contract->id;
            $contract_data->name = contract_name($contract);
            $contract_data->practices = [];
            $contract_data->payment_type_id = $contract->payment_type_id;
            $contract_data->contract_type_id = $contract->contract_type_id;
            $contract_data->contract_name_id = $contract->contract_name_id;
            $practices = Practice::select("practices.*")
                ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'practices.id')
                ->join('physician_contracts', function ($join) {
                    $join->on('physician_contracts.physician_id', '=', 'physician_practice_history.physician_id');
                })
                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                ->join('agreements', 'practices.hospital_id', '=', 'agreements.hospital_id')
                ->where('agreements.id', '=', $agreement->id)
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                ->where('contracts.payment_type_id', '=', $contract->payment_type_id)
                ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                ->whereRaw('physician_practice_history.start_date <= now()')
                ->whereRaw('physician_practice_history.end_date >= now()')
                ->whereNull('contracts.deleted_at')
                ->whereNull('physician_contracts.deleted_at')
                ->groupBy('practices.id')
                ->orderBy('practices.name')
                ->get();

            if (count($practices) > 0) {
                foreach ($practices as $index => $practice) {
                    $practice_id[] = $practice->id;
                    $practice_data = new StdClass();
                    $practice_data->id = $practice->id;
                    $practice_data->name = $practice->name;
                    $practice_data->physicians = [];
                    $practice_data->first = $index == 0;

                    $physicians = PhysicianPracticeHistory::select(
                        DB::raw("physician_practice_history.*"),
                        DB::raw("contracts.id as contract_id"))
                        ->join('physician_contracts', function ($join) {
                            $join->on('physician_contracts.physician_id', '=', 'physician_practice_history.physician_id');
                            $join->on('physician_contracts.practice_id', '=', 'physician_practice_history.practice_id');

                        })
                        ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                        ->join('physician_practices', function ($join) {
                            $join->on('physician_practices.physician_id', '=', 'physician_practice_history.physician_id');
                            $join->on('physician_practices.practice_id', '=', 'physician_practice_history.practice_id');

                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where('contracts.contract_name_id', '=', $contract->contract_name_id)
                        ->where('contracts.payment_type_id', '=', $contract->payment_type_id)
                        ->where('contracts.contract_type_id', '=', $contract->contract_type_id)
                        ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                        ->where('physician_practice_history.practice_id', '=', $practice->id)
                        ->where('physician_practice_history.end_date', '>=', $agreement->start_date)/* for showing practices within agreement */
                        ->whereRaw('physician_practice_history.start_date <= now()')
                        ->whereRaw('physician_practice_history.end_date >= now()')
                        ->whereNull('contracts.deleted_at')
                        ->whereNull('physician_contracts.deleted_at')
                        ->where('physician_practices.practice_id', '=', $practice->id)
                        ->where('contracts.id', '=', $contract->id)
                        ->whereNull('physician_practices.deleted_at')
                        ->orderBy('physician_practice_history.first_name')
                        ->orderBy('physician_practice_history.last_name')
                        ->get();

                    foreach ($physicians as $index => $physician) {

                        $physician_data = new StdClass();
                        $physician_data->id = $physician->physician_id;
                        $physician_data->contract_id = $physician->contract_id;
                        $physician_data->name = "{$physician->last_name}, {$physician->first_name}";
                        $physician_data->first = $index == 0;
                        $practice_data->physicians[] = $physician_data;
                    }

                    if (count($practice_data->physicians) > 0) {
                        $contract_data->practices[] = $practice_data;
                    }
                }
            }
            if (count($practice_id) <= 0) {
                $practice_id[] = 0;
            }

            $data[] = $contract_data;
        }
        return $data;
    }

    public static function getHoursAmount($contract, $physician_id, $practice_id, $start_date, $end_date, $contract_month)
    {
        $result = array();
        $flag = 0;
        $hours = 0;
        $remaining = 0.0;
        $start_date = mysql_date($start_date);
        $end_date = mysql_date($end_date);
        $final_payment = 0;
        $logs = PhysicianLog::select(
            DB::raw("actions.name as action"),
            DB::raw("actions.action_type_id as action_type_id"),
            DB::raw("physician_logs.date as date"),
            DB::raw("physician_logs.log_hours as worked_hours"),
            DB::raw("physician_logs.signature as signature"),
            DB::raw("physician_logs.approval_date as approval_date"),
            DB::raw("physician_logs.details as notes")
        )
            ->join("actions", "actions.id", "=", "physician_logs.action_id")
            ->where("physician_logs.contract_id", "=", $contract->id)
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->where("physician_logs.practice_id", "=", $practice_id)
            ->whereBetween("physician_logs.date", [$start_date, $end_date])
            ->orderBy("physician_logs.date", "asc")
            ->get();

        $calculated_payment = 0.0;
        $amount_paid = 0.0;
        if ($contract->payment_type_id != PaymentType::PER_DIEM) {

            if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                $contractRates = ContractRate::where('contract_id', '=', $contract->id)
                    ->where("status", "=", '1')
                    ->orderBy("effective_start_date", 'DESC')
                    ->get();
                $ratesArray = array();
                $temp_range_arr = array();
                $effective_start_date = "";
                $effective_end_date = "";
                foreach ($contractRates as $contractRate) {
                    $temp_range = ["rate_index" => $contractRate->rate_index,
                        "start_day" => $contractRate->range_start_day,
                        "end_day" => $contractRate->range_end_day,
                        "rate" => $contractRate->rate];
                    array_push($temp_range_arr, $temp_range);
                    $effective_start_date = $contractRate->effective_start_date;
                    $effective_end_date = $contractRate->effective_end_date;
                }

                $ratesArray[] = ["start_date" => $effective_start_date,
                    "end_date" => $effective_end_date,
                    "range" => $temp_range_arr
                ];
            } else if ($contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {
                $rate = ContractRate::getRate($contract->id, $start_date, ContractRate::MONTHLY_STIPEND_RATE);
            } else {
                $rate = ContractRate::getRate($contract->id, $start_date, ContractRate::FMV_RATE);
            }
        } else {
            //Physician to multiple hospital by 1254 : added rate to zero for  exception 'ErrorException' with message 'Undefined variable: rate'
            $rate = 0;
            $weekdayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKDAY_RATE);
            $weekendRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKEND_RATE);
            $holidayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::HOLIDAY_RATE);
            $oncallRate = ContractRate::getRate($contract->id, $start_date, ContractRate::ON_CALL_RATE);
            $calledbackRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_BACK_RATE);
            $calledInRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_IN_RATE);
        }
        $unique_month_arr = array();
        $worked_hours_arr = array();
        $unique_month_arr = array();
        $unique_month_arr = array();
        $unique_month_arr = array();
        $unique_month_arr = array();
        foreach ($logs as $log) {
            if (($log->approval_date != '0000-00-00') || ($log->signature != 0)) {
                $logduration = $log->worked_hours;

                if ($contract->payment_type_id != PaymentType::PER_DIEM) {

                    if ($contract->payment_type_id == PaymentType::PSA) {
                        $rate = 0;
                        if ($contract->wrvu_payments) {
                            $rate = Contract::getPsaRate($contract->id, $logduration);
                        }
                    }
                } else {

                    if (strlen(strstr(strtoupper($log->action), "WEEKDAY")) > 0) {
                        $rate = $weekdayRate;
                    } else if (strlen(strstr(strtoupper($log->action), "WEEKEND")) > 0) {
                        $rate = $weekendRate;
                    } else if (strlen(strstr(strtoupper($log->action), "HOLIDAY")) > 0) {

                        $rate = $holidayRate;
                    } else if ($log->action == "On-Call") {

                        $rate = $oncallRate;
                    } else if ($log->action == "Called-Back") {

                        $rate = $calledbackRate;
                    } else if ($log->action == "Called-In") {

                        $rate = $calledInRate;
                    }
                }

                if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    foreach ($ratesArray as $rates) {
                        if (strtotime($rates['start_date']) <= strtotime($log['date']) && strtotime($rates['end_date']) >= strtotime($log['date'])) {
                            $payment_ranges = $rates['range'];
                        }
                    }

                    $logDate = strtotime($log['date']);
                    $log_month = date("F", $logDate);
                    $log_year = date("Y", $logDate);
                    $log_date = $contract->id . '-' . $log_month . '-' . $log_year;

                    /**
                     * Below condition is used for calculating worked_hours and expected_payment based on unique month
                     */
                    if (in_array($log_date, $unique_month_arr)) {
                        $worked_hours_arr[$log_date] += $log['worked_hours'];
                    } else {
                        array_push($unique_month_arr, $log_date);
                        $worked_hours_arr[$log_date] = 0.00;
                        $worked_hours_arr[$log_date] += $log['worked_hours'];
                    }

                    $hours += $logduration;
                } else if ($contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {
                    $logpayment = $logduration * $rate;
                    $hours += $logduration;
                    $calculated_payment = $rate;
                } else {
                    $logpayment = $logduration * $rate;
                    $hours += $logduration;
                    $calculated_payment = $calculated_payment + $logpayment;
                }
            }

        }

        if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            foreach ($worked_hours_arr as $val_day_hour) {
                $total_day = $val_day_hour;
                $temp_day_remaining = $total_day;
                $temp_calculated_payment = 0.00;

                foreach ($payment_ranges as $range_val_arr) {
                    $start_day = 0;
                    $end_day = 0;
                    $rate = 0.00;
                    extract($range_val_arr); // This line will convert the key into variable to create dynamic ranges from received data.
                    if ($total_day >= $start_day) {
                        if ($temp_day_remaining > 0) {
                            $days_in_range = ($end_day - $start_day) + 1; // Calculating the number of days in a range.
                            if ($temp_day_remaining < $days_in_range) {
                                $temp_calculated_payment += $temp_day_remaining * $rate;
                            } else {
                                $temp_calculated_payment += $days_in_range * $rate;
                            }
                            $temp_day_remaining = $temp_day_remaining - $days_in_range;
                        }
                    } else if ($temp_day_remaining >= 0) {
                        $temp_calculated_payment += $temp_day_remaining * $rate;
                        $temp_day_remaining = 0;
                    }

                }
                $calculated_payment = $calculated_payment + $temp_calculated_payment;
            }
        }

        //amount paid from Hospital payment tab
        $amount_paid_hospital = DB::table('amount_paid')
            ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"),
                DB::raw("sum(amount_paid.final_payment) as final_payment"))
            ->where('physician_id', '=', $physician_id)
            ->where('contract_id', '=', $contract->id)
            ->where('practice_id', '=', $practice_id)
            ->where("start_date", '=', $start_date)
            ->where("end_date", '=', $end_date)
            ->first();
        if ($amount_paid_hospital->amount_paid_hospital == null) {
            $amount_paid_hospital->amount_paid_hospital = 0;
            $amount_paid_hospital->final_payment = 0;
        }


        //$amount_paid = $amount_paid_hospital->amount_paid_hospital + $amount_paid_physician->amount_paid_physician;
        $amount_paid = $amount_paid_hospital->amount_paid_hospital;
        $final_payment = $amount_paid_hospital->final_payment;
        /*if contract type is co-management then expected payment will be calculated using following condtion:
         for 5 months of contract if physician has worked for min hours he should see expected payment as FMV * expected hours
         from 6 months onwards he will see payment only if he works for at least expected hours till this month
          */

        if ($amount_paid == 0) {

            if ($contract->payment_type_id != PaymentType::STIPEND) {

                if ($contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {
                    if ($contract->min_hours > $hours) {
                        $remaining = 0;
                    } else {
                        $remaining += $calculated_payment;
                    }
                } else {
                    $remaining += $calculated_payment;
                }
            } else {
                $contract->rate = $rate;
                $contract->amount_paid = $amount_paid;
                $contract->worked_hours = $hours;
                $contract->contract_month = $contract_month;
                $contract->physician_id = $physician_id;
                $contract->month_end_date = $end_date;
                $contract->practice_id = $practice_id;
                $remaining = self::getRemainingAmount($contract);
            }
        } else {

            $agreement_date = date("m/d/Y", strtotime($start_date));
            $agreement_start_date = with(new DateTime($agreement_date))->setTime(0, 0, 0);
            $deployment_start_date = getDeploymentDate("ContractRateUpdate");

            if ($agreement_start_date >= $deployment_start_date) {
                if (number_format($calculated_payment, 2) > number_format($amount_paid_hospital->amount_paid_hospital, 2))//this condition added for calculating extrapayment for rejected logs
                {
                    if ($contract->payment_type_id != PaymentType::STIPEND) {
                        $remaining += $calculated_payment;
                    } else {
                        $contract->rate = $rate;
                        $contract->amount_paid = $amount_paid;
                        $contract->worked_hours = $hours;
                        $contract->contract_month = $contract_month;
                        $contract->physician_id = $physician_id;
                        $contract->month_end_date = $end_date;
                        $contract->practice_id = $practice_id;
                        $remaining = self::getRemainingAmount($contract);
                    }
                } else {
                    $remaining = 0;
                }
            } else {
                $remaining = 0;
            }
        }

        $result['hours'] = $hours;
        $result['remaining'] = $remaining;
        $result['final_payment'] = $final_payment;
        return $result;
    }


    public static function getRemainingAmount($contract)
    {
        $remaining = 0;
        if ($contract->contract_month > Contract::CO_MANAGEMENT_MIN_MONTHS) {
            $expected_hours = $contract->contract_month * $contract->expected_hours;
            $query = PhysicianLog::select(
                DB::raw("sum(physician_logs.duration) as sum_worked_hours")
            )
                ->where("physician_logs.contract_id", "=", $contract->id)
                ->where("physician_logs.physician_id", "=", $contract->physician_id)
                ->where("physician_logs.practice_id", "=", $contract->practice_id)
                ->where("physician_logs.date", "<=", mysql_date($contract->month_end_date))
                ->where(function ($query) {
                    $query->where('physician_logs.approval_date', '!=', '0000-00-00')
                        ->orWhere('physician_logs.signature', '>', 0);
                })
                ->orderBy("physician_logs.date", "asc");
            $sum = $query->first();
            if (isset($contract->agreement_id)) {
                $agreement = self::findOrFail($contract->agreement_id);
            } else {
                $agreement = self::select("agreements.id as id", "agreements.start_date as start_date")
                    ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                    ->where("contracts.id", "=", $contract->id)->first();
            }
            $amount_paid_hospital = DB::table('amount_paid')
                ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid"))
                ->where('physician_id', '=', $contract->physician_id)
                ->where('contract_id', '=', $contract->id)
                ->where('practice_id', '=', $contract->practice_id)
                ->where("start_date", '>=', mysql_date($agreement->start_date))
                ->where("end_date", '<=', mysql_date($contract->month_end_date))
                ->first();
            if ($sum->sum_worked_hours > 0) {
                $fmv = $amount_paid_hospital->amount_paid / $sum->sum_worked_hours;
            } else {
                $fmv = $amount_paid_hospital->amount_paid;/* if sum worked hours is 0*/
            }

            if ($contract->min_hours <= $contract->worked_hours && $fmv <= $contract->rate) {
                $calculated_payment = $contract->expected_hours * $contract->rate;
                if ($contract->amount_paid == 0) {
                    $remaining = $calculated_payment;
                } else {
                    $remaining = $calculated_payment - $contract->amount_paid;
                }
            } else {
                $remaining = 0;
            }
        } else {
            if ($contract->min_hours <= $contract->worked_hours) {
                $calculated_payment = $contract->expected_hours * $contract->rate;
                if ($contract->amount_paid == 0) {
                    $remaining = $calculated_payment;
                } else {
                    $remaining = $calculated_payment - $contract->amount_paid;/*for partial payment*/
                }
            } else {
                $remaining = 0;
            }

        }
        return $remaining;
    }

    public static function getAgreementPaymentRequireInfo($id, $practice_id, $payment_type_id, $contract_type_id, $physician_id, $contract_month, $cname_id)
    {
        try {
            $deployment_date = getDeploymentDate("ContractRateUpdate");
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $stmt = $pdo->prepare('call sp_agreement_payment_info_v4(?,?,?,?,?,?,?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
            $stmt->bindValue((1), $id);
            $stmt->bindValue((2), $contract_month);
            $stmt->bindValue((3), $practice_id);
            $stmt->bindValue((4), $payment_type_id);
            $stmt->bindValue((5), $contract_type_id);
            $stmt->bindValue((6), $physician_id);
            $stmt->bindValue((7), $cname_id);
            $stmt->bindValue((8), $deployment_date->format('Y-m-d'));
            $exec = $stmt->execute();

            $results = [];
            do {
                try {
                    $value = $stmt->fetchAll(PDO::FETCH_OBJ);
                    if ($value) {
                        $results[] = $value;
                    }
                } catch (\Exception $ex) {
                }
            } while ($stmt->nextRowset());

        } catch (\Exception $e) {
        }


        $payable_contracts_data = array();
        $payable_contract_data = new StdClass();
        $payable_physician_data = new StdClass();
        $payable_practice_data = new StdClass();
        $current_cname_id = 0;
        $current_practice_id = 0;
        $current_physician_id = 0;
        $current_contract_id = 0;
        $current_amount_paid_id = 0;
        $processed_amounts = [];

        if (count($results) > 0) {
            foreach ($results[0] as $result) {
                try {
                    if ($result->is_amount_remaining == 1 || $result->amount_paid > 0) {
                        $contract_obj = Contract::findOrFail($result->contract_id);
                        // Log::info("SP call result", array($result));

                        if ($result->contract_id != $current_contract_id) {

                            $current_contract_id = $result->contract_id;
                            $payable_contract_data = new StdClass();
                            $payable_contract_data->id = $result->contract_id;
                            $payable_contract_data->name = $result->contract_name;
                            $payable_contract_data->is_shared_contract = $result->is_shared_contract;
                            $payable_contract_data->practices = [];
                            $payable_contract_data->payment_type_id = $result->payment_type_id;
                            $payable_contract_data->contract_type_id = $result->contract_type_id;
                            $payable_contract_data->contract_name_id = $result->contract_name_id;

                            $payable_practice_data = new StdClass();
                            $payable_practice_data->id = $result->practice_id;
                            $payable_practice_data->name = $result->practice_name;
                            $payable_practice_data->expected_practice_total = $result->remaining_amount;

                            $payable_practice_data->monthly_max_hours = number_format($result->contract_max_hours * $result->fmv_rate, 2, '.', '');
                            $payable_practice_data->remaining_amount = $result->remaining_amount;
                            $payable_practice_data->annual_max_pay = $result->annual_max_payment;
                            $payable_practice_data->practice_total = 0;
                            $payable_practice_data->amountPaid = [];

                            $payable_practice_data->physicians = [];

                            if ($payable_practice_data->remaining_amount >= 0) {
                                if ($payable_practice_data->remaining_amount == 0) {
                                    $payable_practice_data->color = "black";
                                } else if (count($payable_practice_data->amountPaid) != 0) {
                                    $payable_practice_data->color = "#999999";
                                } else {
                                    $payable_practice_data->color = "#f68a1f";
                                }
                            } else {
                                $payable_practice_data->color = "red";
                            }

                            $payable_contract_data->practices[] = $payable_practice_data;

                            foreach ($results[1] as $physician_data) {
                                if ($physician_data->contract_id == $result->contract_id) {
                                    $payable_physician_data = new StdClass();
                                    $payable_physician_data->id = $physician_data->physician_id;
                                    $payable_physician_data->contract_id = $result->contract_id;
                                    $payable_physician_data->name = $physician_data->physician_name;
                                    $payable_physician_data->worked_hours = $physician_data->log_hours;
                                    $payable_physician_data->remaining_amount = $result->remaining_amount;
                                    $payable_physician_data->expected_practice_total = $result->remaining_amount;
                                    $payable_physician_data->monthly_max_hours = number_format($result->contract_max_hours * $result->fmv_rate, 2, '.', '');
                                    $payable_physician_data->annual_max_pay = $result->annual_max_payment;
                                    $payable_physician_data->amountPaid = [];

                                    if ($payable_physician_data->remaining_amount >= 0) {
                                        if ($payable_physician_data->remaining_amount == 0) {
                                            $payable_physician_data->color = "black";
                                        } else if (count($payable_physician_data->amountPaid) != 0) {
                                            $payable_physician_data->color = "#999999";
                                        } else {
                                            $payable_physician_data->color = "#f68a1f";
                                        }
                                    } else {
                                        $payable_physician_data->color = "red";
                                    }

                                    $payable_contract_data->practices[0]->physicians[] = $payable_physician_data;
                                }
                            }

                            //Amounts Object block
                            if ($result->amount_paid_id != $current_amount_paid_id && $result->amount_paid_id != 0 && !in_array($result->amount_paid_id, $processed_amounts)) {
                                $amountpaid = new StdClass();
                                $amountpaid->id = $result->amount_paid_id;
                                $amountpaid->amountPaid = $result->amount_paid;
                                $amountpaid->final_payment = $result->final_payment;
                                $amountpaid->invoice_no = $result->invoice_no;
                                $payable_practice_data->amountPaid[] = $amountpaid;
                                $payable_physician_data->amountPaid[] = $amountpaid;
                                $payable_contract_data->practices[0]->practice_total += $amountpaid->amountPaid;
                                $current_amount_paid_id = $result->amount_paid_id;
                                $processed_amounts[] = $result->amount_paid_id;
                            }

                            $current_contract_id = $result->contract_id;

                            $payable_contracts_data[] = $payable_contract_data;
                        } else {
                            //Amounts Object block
                            if ($result->amount_paid_id != $current_amount_paid_id && $result->amount_paid_id != 0 && !in_array($result->amount_paid_id, $processed_amounts)) {
                                $amountpaid = new StdClass();
                                $amountpaid->id = $result->amount_paid_id;
                                $amountpaid->amountPaid = $result->amount_paid;
                                $amountpaid->final_payment = $result->final_payment;
                                $amountpaid->invoice_no = $result->invoice_no;
                                $payable_practice_data->amountPaid[] = $amountpaid;
                                $payable_physician_data->amountPaid[] = $amountpaid;
                                $payable_practice_data->practice_total += $amountpaid->amountPaid;

                                $current_amount_paid_id = $result->amount_paid_id;
                                $processed_amounts[] = $result->amount_paid_id;
                            }
                        }
                    }

                } catch (\Exception $e) {
                    Log::info("Agreement@getAgreementPaymentRequireInfo Error: " . $e->getMessage());
                }
            }
        }
        $agreement_data['contracts_data'] = $payable_contracts_data;
        return $agreement_data;
    }

    public static function getHoursAndPaymentDetails($start_date, $end_date, $contract_id, $physician_id, $practice_id, $contract_month)
    {
        $result = array();
        $remaining = 0.0;
        $annual_remaining = 0.0;
        $hours = 0;
        $contract = Contract::findOrFail($contract_id);
        $flag = 0;
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
            ->where("physician_logs.contract_id", "=", $contract_id)
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->where("physician_logs.practice_id", "=", $practice_id)
            ->whereBetween("physician_logs.date", [mysql_date($start_date), mysql_date($end_date)])
            ->orderBy("physician_logs.date", "asc")
            ->get();

        $calculated_payment = 0.0;
        $amount_paid = 0.0;

        if ($contract->payment_type_id != PaymentType::PER_DIEM) {
            $rate = ContractRate::getRate($contract->id, $start_date, ContractRate::FMV_RATE);
        } else {
            $weekdayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKDAY_RATE);
            $weekendRate = ContractRate::getRate($contract->id, $start_date, ContractRate::WEEKEND_RATE);
            $holidayRate = ContractRate::getRate($contract->id, $start_date, ContractRate::HOLIDAY_RATE);
            $oncallRate = ContractRate::getRate($contract->id, $start_date, ContractRate::ON_CALL_RATE);
            $calledbackRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_BACK_RATE);
            $calledInRate = ContractRate::getRate($contract->id, $start_date, ContractRate::CALLED_IN_RATE);
        }
        $payment_type_id = $contract->payment_type_id;
        foreach ($logs as $log) {
            if (($log->approval_date != '0000-00-00') || ($log->signature != 0)) {
                $logduration = $log->worked_hours;

                if ($contract->payment_type_id != PaymentType::PER_DIEM) {

                    if ($contract->payment_type_id == PaymentType::PSA) {
                        if ($contract->wrvu_payments) {
                            $rate = 0;
                            $rate = Contract::getPsaRate($contract->id, $logduration);
                        }
                    }
                } else {
                    if (strlen(strstr(strtoupper($log->action), "WEEKDAY")) > 0) {

                        $rate = $weekdayRate;
                    } else if (strlen(strstr(strtoupper($log->action), "WEEKEND")) > 0) {

                        $rate = $weekendRate;
                    } else if (strlen(strstr(strtoupper($log->action), "HOLIDAY")) > 0) {

                        $rate = $holidayRate;
                    } else if ($log->action == "On-Call") {

                        $rate = $oncallRate;
                    } else if ($log->action == "Called-Back") {

                        $rate = $calledbackRate;
                    } else if ($log->action == "Called-In") {

                        $rate = $calledInRate;
                    }
                }
                $logpayment = $logduration * $rate;
                $hours += $logduration;
                $calculated_payment = $calculated_payment + $logpayment;
            }
        }

        $amount_paid_hospital = DB::table('amount_paid')
            ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"))
            ->where('physician_id', '=', $physician_id)
            ->where('contract_id', '=', $contract_id)
            ->where('practice_id', '=', $practice_id)
            ->where("start_date", '=', mysql_date($start_date))
            ->where("end_date", '=', mysql_date($end_date))
            ->first();
        if ($amount_paid_hospital->amount_paid_hospital == null) {
            $amount_paid_hospital->amount_paid_hospital = 0;
        }

        $amount_paid = $amount_paid_hospital->amount_paid_hospital;
        if ($contract->payment_type_id != PaymentType::STIPEND) {
            $remaining += $calculated_payment - $amount_paid;
        } else {
            $contract->rate = $rate;
            $contract->amount_paid = $amount_paid;
            $contract->worked_hours = $hours;
            $contract->contract_month = $contract_month;
            $contract->physician_id = $physician_id;
            $contract->month_end_date = $end_date;
            $contract->practice_id = $practice_id;
            $remaining = Agreement::getRemainingAmount($contract);
        }
        $worked_hours = $hours;
        $remaining_payment_details = round($remaining, 2);

        $max_hour = DB::table('contracts')
            ->where('physician_id', '=', $physician_id)
            ->where("agreement_id", "=", $contract->agreement_id)
            ->where("id", "=", $contract->id)
            ->first();
        if (isset($max_hour->max_hours)) {
            $days = days($start_date, $end_date);
            $monthly_max_hours = $max_hour->max_hours * $max_hour->rate;
            $max_hours = round($monthly_max_hours, 2);
        }

        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $agreement = Agreement::findOrFail($contract->agreement_id);
            $contractDateBegin = date('Y-m-d', strtotime($agreement->start_date));
            $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($agreement->start_date)));
            $set = false;
            while (!$set) {
                if ((date('Y-m-d', strtotime(Request::input('start_date'))) >= $contractDateBegin) && (date('Y-m-d', strtotime(Request::input('end_date'))) <= $contractDateEnd)) {
                    $set = true;
                } else {
                    $contractDateBegin = $contractDateEnd;
                    $contractDateEnd = date('Y-m-d', strtotime('+1 years', strtotime($contractDateBegin)));
                    $set = false;
                }
            }
            $amount_paid_hospital_in_year = DB::table('amount_paid')
                ->select(DB::raw("sum(amount_paid.amountPaid) as amount_paid_hospital"))
                ->where('contract_id', '=', $contract->id)
                ->where("start_date", '>=', $contractDateBegin)
                ->where("end_date", '<=', $contractDateEnd)
                ->first();
            if ($amount_paid_hospital_in_year->amount_paid_hospital == null) {
                $amount_paid_hospital_in_year->amount_paid_hospital = 0;
            }
            $expected_payment_to_be_paid = $contract->annual_cap * $contract->rate;
            $annual_remaining = $expected_payment_to_be_paid - $amount_paid_hospital_in_year->amount_paid_hospital;
            if ($annual_remaining < 0) {

                $annual_max_pay = 0.0;
            } else {

                $annual_max_pay = round($annual_remaining, 2);
            }
        } else {

            $annual_max_pay = 0.0;
        }

        $result["worked_hours"] = $worked_hours;

        $result["monthly_max_hours"] = $max_hours;
        $result["remaining_amount"] = $remaining_payment_details;
        $result["annual_max_pay"] = $annual_max_pay;

        $result["payment_type_id"] = $payment_type_id;
        return $result;

    }

    public static function testSP()
    {

        try {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $stmt = $pdo->prepare('call sp_agreement_payment_info(?,?,?,?,?,?,?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
            $stmt->bindValue((1), 390);
            $stmt->bindValue((2), 29);
            $stmt->bindValue((3), 0);
            $stmt->bindValue((4), 0);
            $stmt->bindValue((5), 0);
            $stmt->bindValue((6), 0);
            $stmt->bindValue((7), 0);
            $stmt->bindValue((8), '2020-12-14');
            $exec = $stmt->execute();
            $results = [];
            do {
                try {
                    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                } catch (\Exception $ex) {
                }
            } while ($stmt->nextRowset());
        } catch (\Exception $e) {

        }

        $payable_contracts_data = array();
        $payable_contract_data = new StdClass();
        $payable_physician_data = new StdClass();
        $payable_practice_data = new StdClass();
        $current_cname_id = 0;
        $current_practice_id = 0;
        $current_physician_id = 0;
        foreach ($results as $result) {
            if ($result->contract_name_id != $current_cname_id) {
                if ($current_physician_id != 0) {
                    $payable_practice_data->physicians[] = $payable_physician_data;
                    if (count($payable_practice_data->physicians) > 0) {
                        $payable_contract_data->practices[] = $payable_practice_data;
                    }
                    if (count($payable_contract_data->practices) > 0) {
                        $payable_contracts_data[] = $payable_contract_data;
                    }
                }
                $current_cname_id = $result->contract_name_id;
                $payable_contract_data = new StdClass();
                $payable_contract_data->id = $result->contract_id;
                $payable_contract_data->name = $result->contract_name;
                $payable_contract_data->practices = [];
                $payable_contract_data->payment_type_id = $result->payment_type_id;
                $payable_contract_data->contract_type_id = $result->contract_type_id;
                $payable_contract_data->contract_name_id = $result->contract_name_id;
                $current_practice_id = 0;
                $current_physician_id = 0;
            }
            if ($result->contract_name_id == $current_cname_id && $result->practice_id != $current_practice_id) {
                if ($current_physician_id != 0) {
                    $payable_practice_data->physicians[] = $payable_physician_data;
                }
                $current_practice_id = $result->practice_id;
                $payable_practice_data = new StdClass();
                $payable_practice_data->id = $result->practice_id;
                $payable_practice_data->name = $result->practice_name;
                $payable_practice_data->expected_practice_total = 0;
                $payable_practice_data->practice_total = 0;
                $payable_practice_data->physicians = [];
                $current_physician_id = 0;
            }
            if ($result->contract_name_id == $current_cname_id && $result->practice_id == $current_practice_id && $result->physician_id != $current_physician_id) {
                if ($current_physician_id != 0) {
                    $payable_practice_data->physicians[] = $payable_physician_data;
                }
                $current_physician_id = $result->physician_id;
                $payable_physician_data = new StdClass();
                $payable_physician_data->id = $result->physician_id;
                $payable_physician_data->contract_id = $result->contract_id;
                $payable_physician_data->name = $result->physician_name;
                $payable_physician_data->worked_hours = $result->worked_hours;
                $payable_physician_data->monthly_max_hours = $result->contract_max_hours;
                $payable_physician_data->remaining_amount = $result->remaining_amount;
                $payable_physician_data->annual_max_pay = 0.00;
                $payable_physician_data->payment_type_id = $result->payment_type_id;
                $payable_practice_data->expected_practice_total += $result->remaining_amount;
                $payable_physician_data->amountPaid = array();
            }
            if ($result->contract_name_id == $current_cname_id && $result->practice_id == $current_practice_id && $result->physician_id == $current_physician_id) {
                $amountpaid = new StdClass();
                $amountpaid->id = $result->amount_paid_id;
                $amountpaid->amountPaid = $result->amount_paid;
                $amountpaid->invoice_no = $result->invoice_no;
                $payable_physician_data->amountPaid[] = $amountpaid;
                $payable_practice_data->practice_total += $amountpaid->amountPaid;

                if ($payable_physician_data->remaining_amount >= 0) {
                    if ($payable_physician_data->remaining_amount == 0) {
                        $payable_physician_data->color = "black";
                    } else if (count($payable_physician_data->amountPaid) != 0) {
                        $payable_physician_data->color = "#999999";
                    } else {
                        $payable_physician_data->color = "#f68a1f";
                    }
                } else {
                    $payable_physician_data->color = "red";
                }
            }


        }
        if ($current_physician_id != 0) {
            $payable_practice_data->physicians[] = $payable_physician_data;
            if (count($payable_practice_data->physicians) > 0) {
                $payable_contract_data->practices[] = $payable_practice_data;
            }
            if (count($payable_contract_data->practices) > 0) {
                $payable_contracts_data[] = $payable_contract_data;
            }
        }
        return $results;
    }

    public static function getHospitalAgreementDataForComplianceReports($user_id, $facility, $contract_type)
    {
        $query = self::select("agreements.*")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($facility != 0) {
            $query = $query->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $query = $query->where("hospitals.id", $hospital_list);
        }
        if ($contract_type != 0) {
            $query = $query->where("contracts.contract_type_id", "=", $contract_type);
        }
        $query = $query
            ->where("hospital_user.user_id", "=", $user_id)
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        $query = $query->distinct();

        return self::getAgreementsDataForReports($query->get());
    }

    public static function getHospitalAgreementDataForPhysiciansComplianceReports($physicians, $user_id, $facility, $contract_type)
    {
        if (empty($physicians)) {
            return [];
        }

        $query = self::select("agreements.*")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->whereIn("physician_contracts.physician_id", $physicians);

        if ($facility != 0) {
            $query = $query->where('agreements.hospital_id', '=', $facility);
        }
        if ($contract_type != -1) {
            $query = $query->where("contracts.contract_type_id", "=", $contract_type);
        }
        $query = $query
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("agreements.deleted_at")
            ->where("agreements.is_deleted", "=", 0)
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        return $query = $query->distinct()->pluck("name", "id");

    }

    public static function getHospitalAgreementDataForApproversComplianceReports($approvers, $user_id, $facility, $contract_type)
    {
        if (empty($approvers)) {
            return [];
        }

        $query = self::select("agreements.*")
            ->join("agreement_approval_managers_info", "agreement_approval_managers_info.agreement_id", "=", "agreements.id")
            ->join("contracts", "contracts.agreement_id", "=", "agreements.id")
            ->whereIn("agreement_approval_managers_info.user_id", $approvers);

        if ($facility != 0) {
            $query = $query->where('agreements.hospital_id', '=', $facility);
        }
        if ($contract_type != -1) {
            $query = $query->where("contracts.contract_type_id", "=", $contract_type);
        }

        $query = $query
            ->whereNull("agreements.deleted_at")
            ->where("agreements.is_deleted", "=", 0)
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        return $query = $query->distinct()->pluck("name", "id");

    }

    /*
    @Description:check Manual end date
    @return: date
    */

    public static function getPhysicianHospitalAgreementData($physician, $contractTypeId = -1, $hospital_id)
    {
        $physician_id = $physician->id;

        $query = $agreements = self::select('agreements.*')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
            ->join('physician_practices', 'physician_practices.hospital_id', '=', 'agreements.hospital_id')
            ->where('physicians.id', '=', $physician_id)
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.end_date >= NOW()')
            ->groupBy('agreements.id')
            ->whereNull("contracts.deleted_at");

        if ($contractTypeId != -1) {
            $query->where("contracts.contract_type_id", "=", $contractTypeId);
        }
        if ($hospital_id != 0) {
            $query->where("physician_practices.hospital_id", "=", $hospital_id);
        }

        return self::getAgreementsDataForReports($query->get());
    }

    public static function invoiceDashboardOnOff()
    {
        ini_set('max_execution_time', 60000000);

        $hospitals = Hospital::select('hospitals.id')
            ->where('invoice_dashboard_on_off', '=', false)
            ->distinct()
            ->get();

        foreach ($hospitals as $hospital) {
            try {
                $deployment_date = getDeploymentDate("ContractRateUpdate");
                $pdo = DB::connection()->getPdo();
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $stmt = $pdo->prepare('call sp_hospital_payment_require_info_v6(?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
                $stmt->bindValue((1), $hospital->id);
                $stmt->bindValue((2), $deployment_date->format('Y-m-d'));
                $exec = $stmt->execute();
                $spresults = [];
                do {
                    try {
                        $spresults[] = $stmt->fetchAll(PDO::FETCH_OBJ);
                    } catch (\Exception $ex) {
                        Log::info("SP sp_hospital_payment_require_info_v6 call Catch Error 1" . $ex->getMessage());
                    }
                } while ($stmt->nextRowset());
            } catch (\Exception $e) {
                Log::info("SP sp_hospital_payment_require_info_v6 call Catch Error 2" . $e->getMessage());
            }

            if (count($spresults) > 0) {
                foreach ($spresults[1] as $result) {
                    if ($result->is_remaining_amount_flag == true) {
                        try {
                            $deployment_date = getDeploymentDate("ContractRateUpdate");
                            $pdo = DB::connection()->getPdo();
                            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                            $stmt = $pdo->prepare('call sp_agreement_payment_info_v4(?,?,?,?,?,?,?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
                            $stmt->bindValue((1), $result->agreement_id);
                            $stmt->bindValue((2), $result->contract_month);
                            $stmt->bindValue((3), $result->practice_id);
                            $stmt->bindValue((4), $result->payment_type_id);
                            $stmt->bindValue((5), $result->contract_type_id);
                            $stmt->bindValue((6), $result->physician_id);
                            $stmt->bindValue((7), $result->contract_name_id);
                            $stmt->bindValue((8), $deployment_date->format('Y-m-d'));
                            $exec = $stmt->execute();
                            $results = [];

                            do {
                                try {
                                    $value = $stmt->fetchAll(PDO::FETCH_OBJ);
                                    if ($value) {
                                        $results = $value;
                                    }
                                } catch (\Exception $ex) {
                                    Log::info("SP sp_agreement_payment_info_v4 call Catch Error 1" . $ex->getMessage());
                                }
                            } while ($stmt->nextRowset());
                        } catch (\Exception $e) {
                            Log::info("SP sp_agreement_payment_info_v4 call Catch Error 2" . $e->getMessage());
                        }

                        if (count($results) > 0) {
                            foreach ($results as $result) {
                                if ($result->is_amount_remaining == true && $result->remaining_amount > 0) {
                                    // add entry in amount paid table
                                    $amount_paid = new Amount_paid();
                                    $amount_paid->physician_id = $result->physician_id;
                                    $amount_paid->amountPaid = $result->remaining_amount;
                                    $amount_paid->start_date = $result->start_date;
                                    $amount_paid->end_date = $result->end_Date;
                                    $amount_paid->contract_id = $result->contract_id;
                                    $amount_paid->practice_id = $result->practice_id;
                                    $amount_paid->is_interfaced = 0;
                                    $amount_paid->final_payment = 0;
                                    $amount_paid->remarks = "Payment submitted invoice not generated";
                                    $amount_paid->save();
                                }
                            }
                        }
                    }
                }
            }
            UpdatePendingPaymentCount::dispatch($hospital->id);
        }
        return 1;
    }

    public static function getHospitalAgreementDataForHealthSystemReports($hospital_id)
    {
        set_time_limit(0);
        $agreements = self::whereIn('hospital_id', $hospital_id)
            ->select("agreements.*")
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc')
            ->whereRaw('agreements.archived = 0')
            ->get();

        $start_end_date = self::whereIn('hospital_id', $hospital_id)
            ->select(DB::raw("MIN(agreements.start_date) as start_date"), DB::raw("MAX(agreements.end_date) as end_date"))
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw('agreements.archived = 0')
            ->first();

        $agreement_start_period = [];
        $agreement_end_period = [];
        $today = date("m/d/Y", strtotime(now()));
        $start_period = date("m/01/Y", strtotime($start_end_date->start_date));
        $end_date = date("m/t/Y", strtotime($start_end_date->end_date));
        $index = 0;

        while (strtotime($start_period) < strtotime($end_date)) {
            $index++;
            $agreement_start_period[$start_period] = $start_period;
            $end_period = date('m/d/Y', strtotime('+1 months', strtotime($start_period)));
            $end_period = date('m/d/Y', strtotime('-1 day', strtotime($end_period)));
            $agreement_end_period[$end_period] = $end_period;
            $start_period = date('m/d/Y', strtotime('+1 months', strtotime($start_period)));
        }

        $results = [];
        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementDataForHealthSystemReport($agreement->id, $agreement_start_period, $agreement_end_period);
        }

        return $results;
    }

    public static function getAgreementDataForHealthSystemReport($agreement, $agreement_start_period, $agreement_end_period)
    {
        $now = new DateTime('now');

        if (is_a($agreement, 'Illuminate\Database\Eloquent\Collection')) {
            $agreement = $agreement->all();
            $agreement = $agreement[0];
        }

        if (!($agreement instanceof Agreement)) {
            $agreement = self::findOrFail($agreement);
        }

        $agreement_data = new StdClass;
        $agreement_data->id = $agreement->id;
        $agreement_data->hospital_id = $agreement->hospital_id;
        $agreement_data->name = $agreement->name;
        $agreement_data->start_date = format_date($agreement->start_date);
        $agreement_data->end_date = format_date($agreement->end_date);
        $agreement_data->term = months($agreement->start_date, $agreement->end_date);
        $agreement_data->months = [];
        $agreement_data->start_dates = $agreement_start_period;
        $agreement_data->end_dates = $agreement_end_period;
        $agreement_data->dates = [];
        $agreement_data->current_month = -1;
        $agreement_data->disable = false;

        return $agreement_data;
    }

    public static function getHospitalAgreementStartEndDate($hospital_id)
    {
        $start_end_date = self::whereIn('hospital_id', $hospital_id)
            ->select(DB::raw("MIN(agreements.start_date) as start_date"), DB::raw("MAX(agreements.end_date) as end_date"))
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->whereRaw('agreements.archived = 0')
            ->first();

        $results = [];
        $agreement_start_period = [];
        $agreement_end_period = [];
        $today = date("m/d/Y", strtotime(now()));
        $start_period = date("m/01/Y", strtotime($start_end_date->start_date));
        $end_date = date("m/t/Y", strtotime($start_end_date->end_date));
        $index = 0;

        while (strtotime($start_period) < strtotime($end_date)) {
            $index++;
            $agreement_start_period[$start_period] = $start_period;
            $end_period = date('m/d/Y', strtotime('+1 months', strtotime($start_period)));
            $end_period = date('m/d/Y', strtotime('-1 day', strtotime($end_period)));
            $agreement_end_period[$end_period] = $end_period;
            $start_period = date('m/d/Y', strtotime('+1 months', strtotime($start_period)));
        }

        $results = [
            "agreement_start_period" => $agreement_start_period,
            "agreement_end_period" => $agreement_end_period
        ];

        return $results;
    }

    public static function getHospitalAgreementDataForAttestationReports($hospitalId, $contractTypeId = -1, $show_archived_flag = 0)
    {

        $query = self::where('hospital_id', '=', $hospitalId)
            ->select("agreements.*")
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.is_deleted = 0')
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc');
        if ($show_archived_flag == 0) {
            $query->whereRaw('agreements.archived = 0');
        }
        if ($contractTypeId != -1) {
            $query->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        }
        return self::getAgreementsDataForAttestationReports($query->get());
    }

    private static function getAgreementsDataForAttestationReports($agreements)
    {
        $results = [];
        foreach ($agreements as $agreement) {
            $results[] = self::getAgreementsDataForAttestationReport($agreement->id);
        }
        return $results;
    }

    public static function getAgreementsDataForAttestationReport($agreement)
    {
        $now = new DateTime('now');
        if (is_a($agreement, 'Illuminate\Database\Eloquent\Collection')) {
            $agreement = $agreement->all();
            $agreement = $agreement[0];
        }
        if (!($agreement instanceof Agreement)) {
            $agreement = self::findOrFail($agreement);
        }
        $agreement_data = new StdClass;
        $agreement_data->id = $agreement->id;
        $agreement_data->hospital_id = $agreement->hospital_id;
        $agreement_data->name = $agreement->name;
        $agreement_data->start_date = format_date($agreement->start_date);
        $agreement_data->end_date = format_date($agreement->end_date);
        $agreement_data->term = months($agreement->start_date, $agreement->end_date);
        $agreement_data->months = [];
        $agreement_data->start_dates = [];
        $agreement_data->end_dates = [];
        $agreement_data->dates = [];
        $agreement_data->current_month = -1;
        $agreement_data->disable = false;
        // Below changes are done based on payment frequency of agreement by akash.
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
        $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
        foreach ($payment_frequency_range as $index => $date_obj) {
            $start_date = date("m/d/Y", strtotime($date_obj['start_date']));
            $end_date = date("m/d/Y", strtotime($date_obj['end_date']));
            $month_data = new StdClass;
            $month_data->number = $index + 1;
            $month_data->start_date = $start_date;
            $month_data->end_date = $end_date;
            $month_data->now_date = $now->format('m/d/Y');
            $month_data->current = ($now->format('m/d/Y') >= $start_date && $now->format('m/d/Y') <= $end_date);
            if ($month_data->current) {
                $agreement_data->current_month = $month_data->number;
            }
            $agreement_data->months[$month_data->number] = $month_data;
            $agreement_data->start_dates[$month_data->start_date] = $month_data->start_date;
            $agreement_data->end_dates[$month_data->end_date] = $month_data->end_date;
            $agreement_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
        }
        return $agreement_data;
    }

    public static function getAllAgreementPaymentRequireInfo($hospital_id, $practice_id, $payment_type_id, $contract_type_id, $physician_id, $start_date, $end_date)
    {
        set_time_limit(0);
        try {
            $deployment_date = getDeploymentDate("ContractRateUpdate");
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $stmt = $pdo->prepare('call sp_all_agreement_payment_info_v1(?,?,?,?,?,?,?,?)', [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
            $stmt->bindValue((1), $hospital_id);
            $stmt->bindValue((2), $practice_id);
            $stmt->bindValue((3), $payment_type_id);
            $stmt->bindValue((4), $contract_type_id);
            $stmt->bindValue((5), $physician_id);
            $stmt->bindValue((6), date('Y-m-d', strtotime($start_date)));
            $stmt->bindValue((7), date('Y-m-d', strtotime($end_date)));
            $stmt->bindValue((8), $deployment_date->format('Y-m-d'));
            $exec = $stmt->execute();

            $results = [];
            do {
                try {
                    $value = $stmt->fetchAll(PDO::FETCH_OBJ);
                    if ($value) {
                        $results[] = $value;
                    }
                } catch (\Exception $ex) {

                }
            } while ($stmt->nextRowset());
            // return $results;
        } catch (\Exception $e) {

        }
        $payable_contracts_data = array();
        $payable_contract_data = new StdClass();
        $payable_physician_data = new StdClass();
        $payable_practice_data = new StdClass();
        $current_cname_id = 0;
        $current_practice_id = 0;
        $current_physician_id = 0;
        $prev_start_date = '0000-00-00';
        $prev_end_date = '0000-00-00';
        $contract_id = 0;
        $current_contract_id = 0;
        $current_amount_paid_id = 0;

        $agreement_data = [];

        if (count($results) > 0) {
            foreach ($results[0] as $result) {
                if ($result->is_amount_remaining == 1) {
                    $contract_obj = Contract::findOrFail($result->contract_id);


                    if ($result->contract_id != $current_contract_id) {

                        $current_contract_id = $result->contract_id;
                        $payable_contract_data = new StdClass();
                        $payable_contract_data->id = $result->contract_id;
                        $payable_contract_data->name = $result->contract_name;
                        $payable_contract_data->start_date = $result->start_date;
                        $payable_contract_data->end_date = $result->end_date;
                        $payable_contract_data->is_shared_contract = $result->is_shared_contract;
                        $payable_contract_data->practices = [];
                        $payable_contract_data->payment_type_id = $result->payment_type_id;
                        $payable_contract_data->contract_type_id = $result->contract_type_id;
                        $payable_contract_data->contract_name_id = $result->contract_name_id;

                        //Practice Object block
                        $payable_practice_data = new StdClass();
                        $payable_practice_data->id = $result->practice_id;
                        $payable_practice_data->name = $result->practice_name;
                        $payable_practice_data->expected_practice_total = $result->remaining_amount;

                        $payable_practice_data->monthly_max_hours = $result->contract_max_hours * $result->fmv_rate;
                        $payable_practice_data->remaining_amount = $result->remaining_amount;
                        $payable_practice_data->annual_max_pay = $result->annual_max_payment;
                        $payable_practice_data->practice_total = 0;
                        $payable_practice_data->amountPaid = [];

                        $payable_practice_data->physicians = [];

                        if ($payable_practice_data->remaining_amount >= 0) {
                            if ($payable_practice_data->remaining_amount == 0) {
                                $payable_practice_data->color = "black";
                            } else if (count($payable_practice_data->amountPaid) != 0) {
                                $payable_practice_data->color = "#999999";
                            } else {
                                $payable_practice_data->color = "#f68a1f";
                            }
                        } else {
                            $payable_practice_data->color = "red";
                        }

                        $payable_contract_data->practices[] = $payable_practice_data;

                        //Physician Object block
                        foreach ($results[1] as $physician_data) {
                            if (($physician_data->contract_id == $result->contract_id) && ($physician_data->practice_id == $result->practice_id) && ($physician_data->start_date == $result->start_date) && ($physician_data->end_date == $result->end_date)) {
                                $payable_physician_data = new StdClass();
                                $payable_physician_data->id = $physician_data->physician_id;
                                $payable_physician_data->contract_id = $result->contract_id;
                                $payable_physician_data->name = $physician_data->physician_name;
                                $payable_physician_data->worked_hours = $physician_data->log_hours;
                                $payable_physician_data->remaining_amount = $result->remaining_amount;
                                $payable_physician_data->expected_practice_total = $result->remaining_amount;
                                $payable_physician_data->monthly_max_hours = $result->contract_max_hours * $result->fmv_rate;
                                $payable_physician_data->annual_max_pay = $result->annual_max_payment;
                                $payable_physician_data->amountPaid = [];

                                if ($payable_physician_data->remaining_amount >= 0) {
                                    if ($payable_physician_data->remaining_amount == 0) {
                                        $payable_physician_data->color = "black";
                                    } else if (count($payable_physician_data->amountPaid) != 0) {
                                        $payable_physician_data->color = "#999999";
                                    } else {
                                        $payable_physician_data->color = "#f68a1f";
                                    }
                                } else {
                                    $payable_physician_data->color = "red";
                                }

                                $payable_contract_data->practices[0]->physicians[] = $payable_physician_data;
                            }
                        }

                        //Amounts Object block
                        if ($result->amount_paid_id != $current_amount_paid_id && $result->amount_paid_id != 0) {
                            $amountpaid = new StdClass();
                            $amountpaid->id = $result->amount_paid_id;
                            $amountpaid->amountPaid = $result->amount_paid;
                            $amountpaid->final_payment = $result->final_payment;
                            $amountpaid->invoice_no = $result->invoice_no;
                            $payable_practice_data->amountPaid[] = $amountpaid;
                            $payable_physician_data->amountPaid[] = $amountpaid;
                            $payable_contract_data->practices[0]->practice_total += $amountpaid->amountPaid;
                            $current_amount_paid_id = $result->amount_paid_id;
                        }

                        $payable_contracts_data[] = $payable_contract_data;

                        $current_contract_id = $result->contract_id;
                        $prev_start_date = $result->start_date;
                        $prev_end_date = $result->end_date;
                    } else {
                        if ($result->start_date == $prev_start_date && $result->end_date == $prev_end_date) {
                            //Amounts Object block
                            if ($result->amount_paid_id != $current_amount_paid_id && $result->amount_paid_id != 0) {
                                $amountpaid = new StdClass();
                                $amountpaid->id = $result->amount_paid_id;
                                $amountpaid->amountPaid = $result->amount_paid;
                                $amountpaid->final_payment = $result->final_payment;
                                $amountpaid->invoice_no = $result->invoice_no;
                                $payable_practice_data->amountPaid[] = $amountpaid;
                                $payable_physician_data->amountPaid[] = $amountpaid;
                                $payable_practice_data->practice_total += $amountpaid->amountPaid;

                                $current_amount_paid_id = $result->amount_paid_id;
                            }
                        } else if ($result->start_date != $prev_start_date && $result->end_date != $prev_end_date) {
                            $payable_contract_data = new StdClass();
                            $payable_contract_data->id = $result->contract_id;
                            $payable_contract_data->name = $result->contract_name;
                            $payable_contract_data->start_date = $result->start_date;
                            $payable_contract_data->end_date = $result->end_date;
                            $payable_contract_data->is_shared_contract = $result->is_shared_contract;
                            $payable_contract_data->practices = [];
                            $payable_contract_data->payment_type_id = $result->payment_type_id;
                            $payable_contract_data->contract_type_id = $result->contract_type_id;
                            $payable_contract_data->contract_name_id = $result->contract_name_id;

                            //Practice Object block
                            $payable_practice_data = new StdClass();
                            $payable_practice_data->id = $result->practice_id;
                            $payable_practice_data->name = $result->practice_name;
                            $payable_practice_data->expected_practice_total = $result->remaining_amount;

                            $payable_practice_data->monthly_max_hours = $result->contract_max_hours * $result->fmv_rate;
                            $payable_practice_data->remaining_amount = $result->remaining_amount;
                            $payable_practice_data->annual_max_pay = $result->annual_max_payment;
                            $payable_practice_data->practice_total = 0;
                            $payable_practice_data->amountPaid = [];

                            $payable_practice_data->physicians = [];

                            if ($payable_practice_data->remaining_amount >= 0) {
                                if ($payable_practice_data->remaining_amount == 0) {
                                    $payable_practice_data->color = "black";
                                } else if (count($payable_practice_data->amountPaid) != 0) {
                                    $payable_practice_data->color = "#999999";
                                } else {
                                    $payable_practice_data->color = "#f68a1f";
                                }
                            } else {
                                $payable_practice_data->color = "red";
                            }

                            $payable_contract_data->practices[] = $payable_practice_data;

                            //Physician Object block
                            foreach ($results[1] as $physician_data) {
                                if (($physician_data->contract_id == $result->contract_id) && ($physician_data->practice_id == $result->practice_id) && ($physician_data->start_date == $result->start_date) && ($physician_data->end_date == $result->end_date)) {
                                    $payable_physician_data = new StdClass();
                                    $payable_physician_data->id = $physician_data->physician_id;
                                    $payable_physician_data->contract_id = $result->contract_id;
                                    $payable_physician_data->name = $physician_data->physician_name;
                                    $payable_physician_data->worked_hours = $physician_data->log_hours;
                                    $payable_physician_data->remaining_amount = $result->remaining_amount;
                                    $payable_physician_data->expected_practice_total = $result->remaining_amount;
                                    $payable_physician_data->monthly_max_hours = $result->contract_max_hours * $result->fmv_rate;
                                    $payable_physician_data->annual_max_pay = $result->annual_max_payment;
                                    $payable_physician_data->amountPaid = [];

                                    if ($payable_physician_data->remaining_amount >= 0) {
                                        if ($payable_physician_data->remaining_amount == 0) {
                                            $payable_physician_data->color = "black";
                                        } else if (count($payable_physician_data->amountPaid) != 0) {
                                            $payable_physician_data->color = "#999999";
                                        } else {
                                            $payable_physician_data->color = "#f68a1f";
                                        }
                                    } else {
                                        $payable_physician_data->color = "red";
                                    }

                                    $payable_contract_data->practices[0]->physicians[] = $payable_physician_data;
                                }
                            }

                            //Amounts Object block
                            if ($result->amount_paid_id != $current_amount_paid_id && $result->amount_paid_id != 0) {
                                $amountpaid = new StdClass();
                                $amountpaid->id = $result->amount_paid_id;
                                $amountpaid->amountPaid = $result->amount_paid;
                                $amountpaid->final_payment = $result->final_payment;
                                $amountpaid->invoice_no = $result->invoice_no;
                                $payable_practice_data->amountPaid[] = $amountpaid;
                                $payable_physician_data->amountPaid[] = $amountpaid;
                                $payable_contract_data->practices[0]->practice_total += $amountpaid->amountPaid;
                                $current_amount_paid_id = $result->amount_paid_id;
                            }

                            $payable_contracts_data[] = $payable_contract_data;

                            $current_contract_id = $result->contract_id;
                            $prev_start_date = $result->start_date;
                            $prev_end_date = $result->end_date;
                        }
                    }
                }
            }


            $agreement_data['contracts_data'] = $payable_contracts_data;
        }

        return $agreement_data;
    }

    public static function getHospitalAgreementDataForReportsBasedOnDate($hospitalId, $contractTypeId = -1, $show_archived_flag = 0, $start_date, $end_date)
    {
        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));

        $query1 = self::where('hospital_id', '=', $hospitalId)
            ->select('agreements.*')
            ->where('agreements.start_date', '<=', $start_date)
            ->where('agreements.end_date', '>=', $start_date)
            ->where("agreements.is_deleted", "=", 0)
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc')
            ->distinct();
        if ($contractTypeId != -1) {
            $query1->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        } else {
            $query1->join("contracts", "contracts.agreement_id", "=", "agreements.id");
        }

        $query2 = self::where('hospital_id', '=', $hospitalId)
            ->select("agreements.*")
            ->where('agreements.start_date', '>=', $start_date)
            ->where('agreements.end_date', '<=', $end_date)
            ->where("agreements.is_deleted", "=", 0)
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc')
            ->distinct();
        if ($contractTypeId != -1) {
            $query2->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        } else {
            $query2->join("contracts", "contracts.agreement_id", "=", "agreements.id");
        }
        $query3 = self::where('hospital_id', '=', $hospitalId)
            ->select("agreements.*")
            ->where('agreements.start_date', '>=', $start_date)
            ->where('agreements.end_date', '>=', $end_date)
            ->where("agreements.is_deleted", "=", 0)
            ->groupBy('agreements.id')
            ->orderBy('agreements.name', 'asc')
            ->distinct();
        if ($contractTypeId != -1) {
            $query3->join("contracts", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.contract_type_id", "=", $contractTypeId);
        } else {
            $query3->join("contracts", "contracts.agreement_id", "=", "agreements.id");
        }

        $query1 = $query1->union($query2)
            ->union($query3)->orderBy('name', 'asc')
            ->get();

        if ($show_archived_flag == 0) {
            $query1->where("agreements.archived", "=", 0);
        }

        return self::getAgreementsDataForReports($query1);
    }

    public function hospital()
    {
        return $this->belongsTo('App\Hospital');
    }

    public function approvers()
    {
        return $this->belongsToMany('App\ApprovalManagerInfo', 'agreement_approval_managers_info', 'user_id');
    }


    public function getPhysicianCount()
    {
        $results = $this->contracts() // 6.1.1.12
        ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->whereNull('physicians.deleted_at')
            ->groupBy("physicians.id")
            ->get();
        return count($results);
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract');
    }

    public function getMonthString($month)
    {
        $agreement_data = self::getAgreementData($this);
        $month_data = $agreement_data->months[$month];

        return "{$month_data->start_date} - {$month_data->end_date}";
    }

    // This function is used to get hospital agreement based on selected date      Rohit Added on 15/09/2022

    public function getAndCheckManualEndDate($agreementId, $manualEndDate, $method)
    {
        $agreementEndDate = DB::table('agreements')
            ->select(DB::raw("agreements.end_date,agreements.start_date,agreements.valid_upto"))
            ->where('agreements.id', '=', $agreementId)
            ->get();
        if (empty($agreementEndDate)) {
            return false;
        } else {
            if ($method == 'create') {
                if ((strtotime($manualEndDate) <= strtotime($agreementEndDate[0]->end_date)) && (strtotime($manualEndDate) > strtotime($agreementEndDate[0]->start_date))) {
                    return true;
                } else {
                    return false;
                }
            } else if ($method == 'edit') {

                if ((strtotime($manualEndDate) <= strtotime($agreementEndDate[0]->end_date)) && (strtotime($manualEndDate) > strtotime($agreementEndDate[0]->start_date))) {
                    return true;
                } else {
                    return false;
                }

            }
        }

    }

    public static function getAgreementsForUser($userId, $hospital_id)
    {
        return self::where('hospital_id', '=', $hospital_id)
            ->select('agreements.*')
            ->whereIn('agreements.id', function ($query) use ($userId) {
                $query->select('agreement_approval_managers_info.agreement_id')->from('agreement_approval_managers_info')->where('user_id', '=', $userId);
            })->get();

    }

    public function getAgreementApprovers(Agreement $agreement)
    {
        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        $approvalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $agreement->id)
            ->where('is_deleted', '=', '0')
            ->orderBy('level')->get();

        return $approvalManagerInfo;

    }

}
