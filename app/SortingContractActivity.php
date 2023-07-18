<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Action;

class SortingContractActivity extends Model
{
    protected $table = 'sorting_contract_activities';

    public static function getSortingContractActivities($contract_id)
    {
        // $sorting_activities = self::select('*')
        //     ->where('sorting_contract_activities.contract_id', '=', $contract_id)
        //     ->orderBy('sorting_contract_activities.sort_order', 'ASC')
        //     ->get();

        $sorting_activities = Action::select('actions.id as action_id', 'actions.name as action_name', 'actions.category_id as category_id', 'sorting_contract_activities.contract_id', 'sorting_contract_activities.sort_order')
            ->join('sorting_contract_activities', 'sorting_contract_activities.action_id', '=', 'actions.id')
            ->where('sorting_contract_activities.contract_id', '=', $contract_id)
            ->where('sorting_contract_activities.is_active', '=', 1)
            ->orderBy('sorting_contract_activities.sort_order', 'ASC')
            ->get();

        return $sorting_activities;
    }
}
