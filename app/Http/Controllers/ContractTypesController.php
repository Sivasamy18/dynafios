<?php

namespace App\Http\Controllers;

use App\ContractType;
use App\Http\Controllers\Validations\ContractTypeValidation;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class ContractTypesController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;

    public function getIndex()
    {
        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 1,
            'appends' => ['sort', 'order'],
            'field_names' => ['name']
        ];

        $data = $this->query('ContractType', $options);

        $data['table'] = View::make('contract_types/_contract_types')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('contract_types/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('contract_types/create');
    }

    public function postCreate()
    {
        $validation = new ContractTypeValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $contractType = new ContractType();
        $contractType->name = Request::input('name');
        $contractType->description = Request::input('description');

        if (!$contractType->save()) {
            return Redirect::back()->with(['error' => Lang::get('contract-types.create_error')]);
        }

        return Redirect::route('contract_types.index')->with([
            'success' => Lang::get('contract-types.create_success')
        ]);
    }

    public function getEdit($id)
    {
        $contractType = ContractType::findOrFail($id);

        return View::make('contract_types/edit')->with(['contractType' => $contractType]);
    }

    public function postEdit($id)
    {
        $contractType = ContractType::findOrFail($id);

        $validation = new ContractTypeValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $contractType->name = Request::input('name');
        $contractType->description = Request::input('description');

        if (!$contractType->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('contract-types.edit_error')])
                ->withInput();
        }

        return Redirect::route('contract_types.index')->with([
            'success' => Lang::get('contract-types.edit_success')
        ]);
    }

    public function getDelete($id)
    {
        $contractType = ContractType::findOrFail($id);

        $count = $contractType->actions()->count() +
            $contractType->contracts()->count()
        ;

        if ($count > 0) {
            return Redirect::back()->with(['error' => Lang::get('contract-types.delete_error')]);
        } else {
            $contractType->delete();
        }

        return Redirect::route('contract_types.index')->with([
            'success' => Lang::get('contract-types.delete_success')
        ]);
    }
}
