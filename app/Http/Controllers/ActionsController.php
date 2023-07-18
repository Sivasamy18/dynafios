<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Validations\ActionValidation;
use App\Action;
use App\ActionCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class ActionsController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;

    public function getIndex()
    { //get index function
        $options = [
            "filter" => Request::input("filter"),
            "sort" => Request::input("sort"),
            "order" => Request::input("order"),
            "sort_min" => 1,
            "sort_max" => 3,
            "appends" => ["sort", "order", "filter"],
            "field_names" => ["name", "category_name", "hosptial_name"]
        ];

        // Action-Redesign by 1254

        $data = $this->query("Action", $options, function ($query, $options) {
            $query->select('actions.*', 'action_categories.name as category_name',
                'hospitals.name as hosptial_name')
                ->join("action_categories", "action_categories.id", "=", "actions.category_id")
                ->leftJoin("hospitals", "hospitals.id", "=", "actions.hospital_id");
            if (Request::hasHeader("Search") && Request::header("Search") != '' && Request::header("Search") != Null) {
                $query = $query->where(function ($query1) {
                    $query1->where("actions.name", "like", "%" . Request::header('Search') . "%")
                        ->orwhere("action_categories.name", "like", "%" . Request::header('Search') . "%")
                        ->orwhere("hospitals.name", "like", "%" . Request::header('Search') . "%");
                });
            }
            if (Request::hasHeader("Filter") && Request::header("Filter") != 0 && Request::header("Filter") != Null) {
                $query = $query->where("actions.category_id", "=", Request::header('Filter'));
            }
            return $query;

        });

        $categories = ActionCategories::pluck('name', 'id');
        $data["categories"] = ['0' => 'All'] + $categories->toArray();
        $data["category"] = Request::hasHeader("Filter") ? Request::header("Filter") : 0;
        $data["table"] = View::make("actions/_actions")->with($data)->render();
        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('actions/index')->with($data);
    }


    public function getCreate()
    {
        //   Action-Redesign by 1254
        //$actionTypes = options(ActionType::all(), 'id', 'name');
        //$contractTypes = options(ContractType::where('id', '<>', 4)->get(), 'id', 'name');
        // $paymentTypes = options(PaymentType::where('id', '<>', 3)->get(), 'id', 'name');

        $categories = options(ActionCategories::all(), 'id', 'name');

        return View::make('actions/create')->with([
            // 'actionTypes' => $actionTypes,
            //'contractTypes' => $contractTypes
            // 'paymentTypes' => $paymentTypes
            'categories' => $categories
        ]);
    }

    public function postCreate()
    {
        $validation = new ActionValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        // Action-Redesign by 1254
        $action = new Action();

        $action->name = Request::input('name');
        $action->category_id = Request::Input('category');
        $existactionforcategory = Action::select("actions.*")
            ->where('actions.name', '=', $action->name)
            ->where("actions.category_id", "=", $action->category_id)
            ->get();

        if (count($existactionforcategory) == 0) {
            if (!$action->save()) {
                return Redirect::back()->with(['error' => Lang::get('actions.create_error')]);
            }

            return Redirect::route('actions.index')->with([
                'success' => Lang::get('actions.create_success')
            ]);
        } else {
            //return Redirect::back()->with(['error' => Lang::get('action_error')]);
            return Redirect::back()->with(['error' => Lang::get('actions.action_error')]);
        }

    }

//   Action-Redesign by 1254
    public function getEdit($id)
    {
        $action = Action::findOrFail($id);
        // $actionTypes = options(ActionType::all(), 'id', 'name');
        //$contractTypes = options(ContractType::where('id', '<>', 4)->get(), 'id', 'name');
        //$paymentTypes = options(PaymentType::where('id', '<>', 3)->get(), 'id', 'name');


        $categories = options(ActionCategories::all(), 'id', 'name');


        //if($action->contract_type->id != ContractType::ON_CALL){
        // if($action->paymentType->id != PaymentType::PER_DIEM){
        return View::make('actions/edit')->with([
            'categories' => $categories,
            'action' => $action,
            //'actionTypes' => $actionTypes,
            // 'paymentTypes' => $paymentTypes
            //'contractTypes' => $contractTypes
        ]);
        //  }

        return Redirect::back()
            ->withErrors("On Call actions are not editable.")
            ->withInput();
    }

    public function postEdit($id)
    {
        $action = Action::findOrFail($id);

        $validation = new ActionValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $action->name = Request::input('name');
        //$action->action_type_id = Request::input('action_type');
        //$action->contract_type_id = Request::input('contract_type');
        //$action->payment_type_id = Request::input('payment_type');
        //   Action-Redesign by 1254
        $action->category_id = Request::input('category');

        //below code is added by akash for validaion of action on edit. 
        $existactionforcategory = Action::select("actions.*")
            ->where('actions.name', '=', $action->name)
            ->where("actions.category_id", "=", $action->category_id)
            ->get();

        if (count($existactionforcategory) > 0) {
            return Redirect::back()
                ->with(['error' => Lang::get('actions.action_error')])
                ->withInput();
        } else {
            if (!$action->save()) {
                return Redirect::back()
                    ->with(['error' => Lang::get('actions.edit_error')])
                    ->withInput();
            }

            return Redirect::route('actions.index')->with([
                'success' => Lang::get('actions.edit_success')
            ]);
        }
    }

    public function getDelete($id)
    {
        $action = Action::findOrFail($id);

        $count = $action->contracts()->count() +
            $action->physicianLogs()->count();

        if ($count > 0) {
            return Redirect::back()->with([
                'error' => Lang::get('actions.delete_error')
            ]);
        } else {
            $action->delete();
        }

        return Redirect::route('actions.index')->with([
            'success' => Lang::get('actions.delete_success')
        ]);
    }

//<!-- Action-Redesign by 1254  added for search action :1202220 -->

    public function postQuery()
    {
        $searchquery = Request::input('query');

        $options = [
            "filter" => Request::input("filter"),
            "sort" => Request::input("sort"),
            "order" => Request::input("order"),
            "sort_min" => 1,
            "sort_max" => 3,
            "appends" => ["sort", "order", "filter"],

            "field_names" => ["name", "category_name"]
        ];

        $searchaction = $searchquery;

        $data = $this->queryWithUnion("Action", $options, function ($query, $options) use ($searchaction) {
            return $query->select('actions.*', DB::raw("action_categories.name as category_name"))
                ->join("action_categories", "action_categories.id", "=", "actions.category_id")
                ->whereIn("actions.category_id", [1, 2, 3])
                ->where("actions.name", "like", "%{$searchaction}%")
                ->orwhere("action_categories.name", "like", "%{$searchaction}%")
                ->orderBy($options['field_names'][$options['sort'] - 1], $options['order'] == 1 ? 'asc' : 'desc');

        });


        $data["table"] = View::make("actions/_actions")->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('actions/index')->with($data);
    }
}
