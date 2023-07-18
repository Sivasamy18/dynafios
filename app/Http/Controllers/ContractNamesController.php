<?php

namespace App\Http\Controllers;

use App\PaymentType;
use App\ContractName;
use App\ContractType;
use App\Http\Controllers\Validations\ContractNameValidation;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class ContractNamesController extends ResourceController
{
    protected $requireAuth = true;
    protected $requireSuperUser = true;

    public function getIndex()
    {
        $options = [
            'sort' => Request::input('sort'),
            'order' => Request::input('order'),
            'sort_min' => 1,
            'sort_max' => 2,
            'appends' => ['sort', 'order'],
            //'field_names' => ['name', 'contract_type_id']
            'field_names' => ['name', 'payment_type_id'],
            'per_page' => 9999
        ];

        $data = $this->query('ContractName', $options);

        $data['table'] = View::make('contract_names/_contract_names')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('contract_names/index')->with($data);
    }

    public function getCreate()
    {
        //$contractTypes = options(ContractType::all(), 'id', 'name');
        $paymentTypes = options(PaymentType::all(), 'id', 'name');

        return View::make('contract_names/create')->with(['paymentTypes' => $paymentTypes]);
    }

    public function postCreate()
    {
        $validation = new ContractNameValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $contractName = new ContractName();
        $contractName->name = Request::input('name');
        //$contractName->contract_type_id = Request::input('contract_type');
        $contractName->payment_type_id = Request::input('payment_type');

        if (!$contractName->save()) {
            return Redirect::back()->with(['error' => Lang::get('contract_names.create_error')]);
        }

        return Redirect::route('contract_names.index')->with([
            'success' => Lang::get('contract_names.create_success')
        ]);
    }

    public function getEdit($id)
    {
        $contractName = ContractName::findOrFail($id);
        //$contractTypes = options(ContractType::all(), 'id', 'name');
        $paymentTypes = options(PaymentType::all(), 'id', 'name');

        return View::make('contract_names/edit')->with([
            'contractName' => $contractName,
            //'contractTypes' => $contractTypes
            'paymentTypes' => $paymentTypes
        ]);
    }

    public function postEdit($id)
    {
        $contractName = ContractName::findOrFail($id);

        $validation = new ContractNameValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $contractName->name = Request::input('name');
        //$contractName->contract_type_id = Request::input('contract_type');
        $contractName->payment_type_id = Request::input('payment_type');

        if (!$contractName->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('contract_names.edit_error')])
                ->withInput();
        }

        return Redirect::route('contract_names.index')->with([
            'success' => Lang::get('contract_names.edit_success')
        ]);
    }

    public function getDelete($id)
    {
        $contractName = ContractName::findOrFail($id);

        if ($contractName->contracts()->count() > 0) {
            return Redirect::back()->with(['error' => Lang::get('contract_names.delete_error')]);
        } else {
            $contractName->delete();
        }

        return Redirect::route('contract_names.index')->with(['success' => Lang::get('contract_names.delete_success')]);
    }
}