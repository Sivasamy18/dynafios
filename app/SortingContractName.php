<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Log;
use Redirect;
use Lang;
use Illuminate\Support\Facades\DB;

class SortingContractName extends Model
{
    protected $table = 'sorting_contract_names';

    public static function getsortingcontractnames($practice_id, $physician_id)
    {
        $results = Contract::select('contracts.id as contract_id', 'contract_names.name as contract_name', 'sorting_contract_names.sort_order as sort_order')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('sorting_contract_names', 'sorting_contract_names.contract_id', '=', 'contracts.id')
            ->where('contracts.archived', '=', false)
            ->where('contracts.manually_archived', '=', false)
            ->where('contracts.end_date', '=', '0000-00-00 00:00:00')
            ->where('sorting_contract_names.physician_id', '=', $physician_id)
            ->where('sorting_contract_names.is_active', '=', 1)
            ->where('sorting_contract_names.practice_id', '=', $practice_id)
            ->where('agreements.start_date', '<=', now())
            ->where('agreements.is_deleted', '=', 0)
            ->orderBy('sorting_contract_names.sort_order', 'ASC')
            ->distinct()
            ->get();

        return $results;
    }

    public static function postSortingContractNames($request)
    {
        $practice_id = $request['contract_list'][0]['practice_id'];
        $physician_id = $request['contract_list'][0]['physician_id'];
        if ($request) {
            if ($request['contract_list']) {
                foreach ($request['contract_list'] as $contract) {
                    $check_exist = self::where('sorting_contract_names.contract_id', '=', $contract['contract_id'])
                        ->where('sorting_contract_names.practice_id', '=', $practice_id)
                        ->where('sorting_contract_names.physician_id', '=', $physician_id)
                        ->where('sorting_contract_names.sort_order', '!=', $contract['sort_order'])
                        ->where('sorting_contract_names.is_active', '=', 1)
                        ->update(['sorting_contract_names.is_active' => 0]);

                    if ($check_exist > 0) {
                        $sort_contract = new SortingContractName();
                        $sort_contract->practice_id = $practice_id;
                        $sort_contract->physician_id = $physician_id;
                        $sort_contract->contract_id = $contract['contract_id'];
                        $sort_contract->sort_order = $contract['sort_order'];
                        $sort_contract->save();
                    }
                }
            }
        }

        // return Redirect::route('physicians.contracts', [$physician_id, $practice_id])->with([
        //     'success' => Lang::get('physicians.contract_sort_order_success')
        // ]);

        return 1;
    }

    public static function updateSortingContractNames()
    {
        ini_set('max_execution_time', 60000000);
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            // ->where('hospitals.id', '=', 47)
            ->distinct()
            ->get();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                $physician_practices = PhysicianPractices::select('physician_practices.*')
                    ->where('physician_practices.hospital_id', '=', $hospital->id)
                    ->whereNull('physician_practices.deleted_at')
                    ->distinct()
                    ->get();

                if (count($physician_practices) > 0) {
                    foreach ($physician_practices as $physician_practice) {
                        $contracts = Contract::select('contracts.*')
                            ->where('contracts.physician_id', '=', $physician_practice->physician_id)
                            ->where('contracts.practice_id', '=', $physician_practice->practice_id)
                            ->whereNull('contracts.deleted_at')
                            ->distinct()
                            ->get();

                        $index = 0;
                        if (count($contracts) > 0) {
                            foreach ($contracts as $contract) {
                                $index++;
                                $sort_contract = new SortingContractName();
                                $sort_contract->practice_id = $physician_practice->practice_id;
                                $sort_contract->physician_id = $physician_practice->physician_id;
                                $sort_contract->contract_id = $contract->id;
                                $sort_contract->sort_order = $index;
                                $sort_contract->save();
                            }
                        } else {
                            // log::info('Contracts not found.);
                        }
                    }
                }
            }
        } else {
            // log::info('Hospitals not found.);
        }
        return 1;
    }

    public static function updateSortingContractActivities()
    {
        ini_set('max_execution_time', 60000000);
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            // ->where('hospitals.id', '=', 254)
            ->distinct()
            ->get();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                $contracts = Contract::select('contracts.*')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->where('agreements.hospital_id', '=', $hospital->id)
                    ->whereNotIn('contracts.payment_type_id', [3, 5])
                    ->whereNull('contracts.deleted_at')
                    ->distinct()
                    ->get();

                if (count($contracts) > 0) {
                    foreach ($contracts as $contract) {
                        $action_contracts = ActionContract::select('action_contract.*')
                            ->where('action_contract.contract_id', '=', $contract->id)
                            ->get();

                        $index = 0;
                        if (count($action_contracts) > 0) {
                            foreach ($action_contracts as $action_contract) {
                                $category_id = Action::select('category_id')->where('actions.id', '=', $action_contract->action_id)->first();

                                if ($category_id && $category_id['category_id'] != 0) {
                                    $index++;
                                    $sorting_contract = new SortingContractActivity();
                                    $sorting_contract->contract_id = $contract->id;
                                    $sorting_contract->category_id = $category_id['category_id'];
                                    $sorting_contract->action_id = $action_contract->action_id;
                                    $sorting_contract->sort_order = $index;
                                    $sorting_contract->save();
                                } else {

                                }
                            }
                        } else {

                        }
                    }
                } else {

                }
            }
        } else {

        }
        return 1;
    }

    public static function updateSortingContractNamesByHospital($hospital_id)
    {
        ini_set('max_execution_time', 60000000);
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            ->where('hospitals.id', '=', $hospital_id)
            ->distinct()
            ->get();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                $physician_practices = PhysicianPractices::select('physician_practices.*')
                    ->where('physician_practices.hospital_id', '=', $hospital->id)
                    ->whereNull('physician_practices.deleted_at')
                    ->distinct()
                    ->get();

                if (count($physician_practices) > 0) {
                    foreach ($physician_practices as $physician_practice) {
                        $contracts = Contract::select('contracts.*')
                            ->where('contracts.physician_id', '=', $physician_practice->physician_id)
                            ->where('contracts.practice_id', '=', $physician_practice->practice_id)
                            ->whereNull('contracts.deleted_at')
                            ->distinct()
                            ->get();

                        $index = 0;
                        if (count($contracts) > 0) {
                            foreach ($contracts as $contract) {
                                $check_exist = SortingContractName::select('*')
                                    ->where('practice_id', '=', $physician_practice->practice_id)
                                    ->where('physician_id', '=', $physician_practice->physician_id)
                                    ->where('contract_id', '=', $contract->id)
                                    ->where('is_active', '=', 1)
                                    ->count();

                                if ($check_exist == 0) {
                                    $results = SortingContractName::select([DB::raw('MAX(sort_order) AS max_sort_order')])
                                        ->where('practice_id', '=', $physician_practice->practice_id)
                                        ->where('physician_id', '=', $physician_practice->physician_id)
                                        ->where('is_active', '=', 1)
                                        ->first();

                                    if ($results) {
                                        $index = $results->max_sort_order + 1;
                                    } else {
                                        $index = 1;
                                    }

                                    $sort_contract = new SortingContractName();
                                    $sort_contract->practice_id = $physician_practice->practice_id;
                                    $sort_contract->physician_id = $physician_practice->physician_id;
                                    $sort_contract->contract_id = $contract->id;
                                    $sort_contract->sort_order = $index;
                                    $sort_contract->save();
                                }
                            }
                        } else {
                            // log::info('Contracts not found.);
                        }
                    }
                }
            }
        } else {
            // log::info('Hospitals not found.);
        }
        return 1;
    }
}
