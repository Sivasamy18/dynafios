<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Request;
use Redirect;
use Lang;
use App\ActionHospital;
use App\HospitalOverrideMandateDetails;
use App\HospitalTimeStampEntry;


class DutyManagement extends Model
{
    protected $table = 'users';

    public static function getDutiesManagement($hospital_id)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 6000);

        $categories = ActionCategories::all();

        $contract_ids = Contract::select('contracts.id as contract_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->where('agreements.hospital_id', '=', $hospital_id)
            ->get();

        $action_contract_array = array();
        $action_contracts = Action::select('actions.id')
            ->join('action_contract', 'action_contract.action_id', '=', 'actions.id')
            ->whereIn('action_contract.contract_id', $contract_ids->toArray())
            ->orderBy('action_contract.id', 'asc')
            ->distinct()
            ->get();

        foreach ($action_contracts as $action_contract) {
            array_push($action_contract_array, $action_contract->id);
        }

        $actions = Action::whereIn('hospital_id', [0, $hospital_id])
            ->orderBy('actions.name', 'asc')
            ->where('actions.name', '!=', "")
            ->where('actions.name', '!=', null)
            ->distinct()
            ->get();

        $hospital_actions = Action::select('actions.*')
            ->join('action_hospitals', 'action_hospitals.action_id', '=', 'actions.id')
            ->where('action_hospitals.hospital_id', '=', $hospital_id)
            ->where('action_hospitals.is_active', '=', 1)
            ->distinct()
            ->get();

        $override_mandate_details = Action::select('actions.*')
            ->join('hospitals_override_mandate_details', 'hospitals_override_mandate_details.action_id', '=', 'actions.id')
            ->where('hospitals_override_mandate_details.hospital_id', '=', $hospital_id)
            ->where('hospitals_override_mandate_details.is_active', '=', 1)
            ->distinct()
            ->get();

        $time_stamp_entry = Action::select('actions.*')
            ->join('hospitals_time_stamp_entry', 'hospitals_time_stamp_entry.action_id', '=', 'actions.id')
            ->where('hospitals_time_stamp_entry.hospital_id', '=', $hospital_id)
            ->where('hospitals_time_stamp_entry.is_active', '=', 1)
            ->distinct()
            ->get();

        $hospital_actions_is_active_false = Action::select('actions.*')
            ->join('action_hospitals', 'action_hospitals.action_id', '=', 'actions.id')
            ->where('action_hospitals.hospital_id', '=', $hospital_id)
            ->where('action_hospitals.is_active', '=', 0)
            ->distinct()
            ->count();


        $data['categories'] = $categories;
        $data['categories_count'] = count($categories);
        $data['actions'] = $actions;
        $data['hospital_actions'] = $hospital_actions;
        $data['mandate_details'] = $hospital_actions;
        $data['override_mandate_details'] = $override_mandate_details;
        $data['override_mandate_details_count'] = count($override_mandate_details);
        $data['quarter_hour_entries'] = $hospital_actions;
        $data['time_stamp_entries'] = $time_stamp_entry;
        $data['time_stamp_entries_count'] = count($time_stamp_entry);
        $data['hospital_actions_count'] = count($hospital_actions);
        $data['hospital_actions_is_active_false'] = $hospital_actions_is_active_false;
        $data['action_contract'] = $action_contract_array;

        return $data;
    }

    public static function postDutiesManagement($hospital_id)
    {
        $categories = ActionCategories::all();
        $custome_action_error = (array)[];

        // Start Custom Action Validation
        foreach ($categories as $category) {
            $category_name = 'customaction_name_' . $category->id;
            $customaction_names = Request::input($category_name);

            if ($customaction_names) {
                $custome_action_category = (array)[];
                foreach ($customaction_names as $elem_id => $customaction_name) {

                    $existactionforcategory = Action::select("actions.*")
                        ->where('actions.name', '=', $customaction_name)
                        ->where("actions.category_id", "=", $category->id)
                        ->where(function ($query) use ($hospital_id) {
                            $query->where("actions.hospital_id", "=", $hospital_id)
                                ->orWhere("actions.hospital_id", "=", 0);
                        })
                        ->get();

                    if (count($existactionforcategory) > 0) {
                        $custome_action_category[$customaction_name] = true;
                    } else {
                        if ($customaction_name != '') {
                            $custome_action_category[$customaction_name] = false;
                        }
                    }
                }
                $custome_action_error[$category_name] = $custome_action_category;
            }
        }

        if ($custome_action_error) {
            foreach ($custome_action_error as $category_name => $action_arr) {
                if (count($action_arr) > 0) {
                    foreach ($action_arr as $action_name => $flag) {
                        if ($flag == true) {
                            return Redirect::back()->with(['action_error' => $custome_action_error])->withInput();
                        }
                    }
                }
            }
        }
        // End Custom Action Validation

        // Add remove action_hospitals
        $actions = Request::input('actions');

        ActionHospital::where('action_hospitals.hospital_id', '=', $hospital_id)
            ->whereNotIn('action_hospitals.action_id', $actions != null ? $actions : [0])
            ->update(['is_active' => 0]);

        if ($actions) {
            ActionHospital::where('action_hospitals.hospital_id', '=', $hospital_id)
                ->whereNotIn('action_hospitals.action_id', $actions != null ? $actions : [0])
                ->where('action_hospitals.is_active', '=', 1)
                ->update(['is_active' => 0]);

            foreach ($actions as $action_id) {
                $action = ActionHospital::select('*')
                    ->where('action_id', '=', $action_id)
                    ->where('hospital_id', '=', $hospital_id)
                    ->where('is_active', '=', 1)
                    ->first();

                if (!$action) {
                    $action_hospital = new ActionHospital();
                    $action_hospital->hospital_id = $hospital_id;
                    $action_hospital->action_id = $action_id;
                    $action_hospital->save();
                }
            }
        } else {
            ActionHospital::where('action_hospitals.hospital_id', '=', $hospital_id)
                ->where('action_hospitals.is_active', '=', 1)
                ->update(['is_active' => 0]);
        }

        // Start Save Custom Action
        foreach ($categories as $category) {
            $customaction_names = Request::input('customaction_name_' . $category->id);

            if ($customaction_names) {
                foreach ($customaction_names as $customaction_name) {
                    if ($customaction_name) {
                        //save into action table
                        $action_custome = new Action();
                        $action_custome->name = $customaction_name;
                        $action_custome->category_id = $category->id;
                        $action_custome->contract_type_id = 0;
                        $action_custome->hospital_id = $hospital_id;
                        $action_custome->save();

                        //save into action_hospitals table
                        $action_hospital = new ActionHospital();
                        $action_hospital->hospital_id = $hospital_id;
                        $action_hospital->action_id = $action_custome->id;
                        $action_hospital->save();

                    }
                }
            }
        }
        // End Custom Action

        // Add remove Override Mandate Details
        $overridemandatedetails = Request::input('overridemandatedetails');

        if ($overridemandatedetails) {
            HospitalOverrideMandateDetails::where('hospitals_override_mandate_details.hospital_id', '=', $hospital_id)
                ->whereNotIn('hospitals_override_mandate_details.action_id', $overridemandatedetails != null ? $overridemandatedetails : [0])
                ->where('hospitals_override_mandate_details.is_active', '=', 1)
                ->update(['hospitals_override_mandate_details.is_active' => 0]);

            foreach ($overridemandatedetails as $action_id) {
                $action = HospitalOverrideMandateDetails::select('*')
                    ->where('action_id', '=', $action_id)
                    ->where('hospital_id', '=', $hospital_id)
                    ->where('is_active', '=', 1)
                    ->first();

                if (!$action) {
                    $hospital_override_mandate_details = new HospitalOverrideMandateDetails();
                    $hospital_override_mandate_details->hospital_id = $hospital_id;
                    $hospital_override_mandate_details->action_id = $action_id;
                    $hospital_override_mandate_details->save();
                }
            }
        } else {
            HospitalOverrideMandateDetails::where('hospitals_override_mandate_details.hospital_id', '=', $hospital_id)
                ->where('hospitals_override_mandate_details.is_active', '=', 1)
                ->update(['hospitals_override_mandate_details.is_active' => 0]);

        }

        // Add remove Time Stamp Entry
        $timestampentry = Request::input('timestampentry');

        if ($timestampentry) {
            HospitalTimeStampEntry::where('hospitals_time_stamp_entry.hospital_id', '=', $hospital_id)
                ->whereNotIn('hospitals_time_stamp_entry.action_id', $timestampentry != null ? $timestampentry : [0])
                ->where('hospitals_time_stamp_entry.is_active', '=', 1)
                ->update(['hospitals_time_stamp_entry.is_active' => 0]);

            foreach ($timestampentry as $action_id) {
                $action = HospitalTimeStampEntry::select('*')
                    ->where('action_id', '=', $action_id)
                    ->where('hospital_id', '=', $hospital_id)
                    ->where('is_active', '=', 1)
                    ->first();

                if (!$action) {
                    $hospital_time_stamp_entry = new HospitalTimeStampEntry();
                    $hospital_time_stamp_entry->hospital_id = $hospital_id;
                    $hospital_time_stamp_entry->action_id = $action_id;
                    $hospital_time_stamp_entry->save();
                }
            }
        } else {
            HospitalTimeStampEntry::where('hospitals_time_stamp_entry.hospital_id', '=', $hospital_id)
                ->where('hospitals_time_stamp_entry.is_active', '=', 1)
                ->update(['hospitals_time_stamp_entry.is_active' => 0]);
        }

        return Redirect::route('hospitals.dutiesmanagement', $hospital_id)->with([
            'success' => Lang::get('hospitals.hospital_activities_success')
        ]);
    }

    public static function updateHospitalActions()
    {
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            ->distinct()
            ->get();

        $categories = ActionCategories::all();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                foreach ($categories as $category) {
                    $actions = Action::whereIn('actions.hospital_id', [0, $hospital->id])
                        ->where('actions.name', '!=', "")
                        ->where('actions.name', '!=', null)
                        ->where('actions.category_id', '=', $category->id)
                        ->orderBy('actions.name', 'asc')
                        ->distinct()
                        ->get();

                    foreach ($actions as $action) {
                        $check_exist = ActionHospital::select('*')
                            ->where('hospital_id', '=', $hospital->id)
                            ->where('action_id', '=', $action->id)
                            ->where('is_active', '=', 1)
                            ->count();

                        if ($check_exist == 0) {
                            $action_hospital = new ActionHospital();
                            $action_hospital->hospital_id = $hospital->id;
                            $action_hospital->action_id = $action->id;
                            $action_hospital->save();
                        } else {
                            log::info('No need to update.');
                        }
                    }
                }
            }
        } else {
            log::info('Hospitals not found.');
        }
        return 1;
    }
}
