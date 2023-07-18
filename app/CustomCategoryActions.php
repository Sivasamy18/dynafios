<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ActionCategories;
use App\Action;
use App\Hospital;
use Log;
use Redirect;
use Lang;

class CustomCategoryActions extends Model
{

    protected $table = 'custom_category_actions';
    public $timestamps = true;

    public static function UpdateCustomCategoriesActions($user_id, $contract_id, $category_id, $action_id, $category_action_name)
    {
        $hospital = Hospital::select('hospitals.*')
            ->join('agreements', 'agreements.hospital_id', '=', 'hospitals.id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->where('contracts.id', '=', $contract_id)
            ->first();

        if ($action_id > 0) {
            $action_name_exist = self::select('*')
                ->where('contract_id', '=', $contract_id)
                ->where('category_id', '=', $category_id)
                ->where('action_id', '!=', $action_id)
                ->where('action_name', '=', $category_action_name)
                ->where('is_active', '=', true)
                ->get();

            $action = Action::select('*')
                ->whereIn('hospital_id', [0, $hospital->id])
                ->where('id', '!=', $action_id)
                ->where('name', '=', $category_action_name)
                ->where('category_id', '=', $category_id)
                ->get();

            if (count($action_name_exist) > 0 || count($action) > 0) {
                return 'action_name_exist';
            }
        } else {
            $category_name_exist = self::select('*')
                ->where('contract_id', '=', $contract_id)
                ->where('category_id', '!=', $category_id)
                ->where('category_name', '=', $category_action_name)
                ->where('is_active', '=', true)
                ->get();

            $categories = ActionCategories::select('*')
                ->where('id', '!=', $category_id)
                ->where('name', '=', $category_action_name)
                ->get();

            if (count($category_name_exist) > 0 || count($categories) > 0) {
                return 'category_name_exist';
            }
        }

        $check_exist = self::select('*')
            ->where('contract_id', '=', $contract_id)
            ->where('category_id', '=', $category_id)
            ->where('is_active', '=', true);

        if ($action_id > 0) {
            $check_exist = $check_exist->where('action_id', '=', $action_id);
        }

        $check_exist = $check_exist->first();

        if ($check_exist) {
            $query = self::select('*')
                ->where('contract_id', '=', $check_exist->contract_id)
                ->where('category_id', '=', $check_exist->category_id);

            if ($action_id > 0) {
                $query = $query->where('action_id', '=', $check_exist->action_id);
            } else {
                $query = $query->where('action_id', '=', null);
            }
            $query = $query->where('is_active', '=', true)
                ->update(array('is_active' => false, 'updated_by' => $user_id));

            $custom_category_actions = new CustomCategoryActions();
            $custom_category_actions->contract_id = $contract_id;
            $custom_category_actions->category_id = $category_id;

            if ($action_id > 0) {
                $custom_category_actions->action_id = $action_id;
                $custom_category_actions->action_name = $category_action_name;
            } else {
                $custom_category_actions->category_name = $category_action_name;
            }

            $custom_category_actions->created_by = $user_id;
            $custom_category_actions->updated_by = $user_id;
            $custom_category_actions->save();
        } else {
            $custom_category_actions = new CustomCategoryActions();
            $custom_category_actions->contract_id = $contract_id;
            $custom_category_actions->category_id = $category_id;

            if ($action_id > 0) {
                $custom_category_actions->action_id = $action_id;
                $custom_category_actions->action_name = $category_action_name;
            } else {
                $custom_category_actions->category_name = $category_action_name;
            }

            $custom_category_actions->created_by = $user_id;
            $custom_category_actions->updated_by = $user_id;
            $custom_category_actions->save();
        }

        return 1;
    }
}
