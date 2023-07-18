<?php

namespace App\Http\Controllers;

use App\PaymentType;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class PaymentTypesController extends ResourceController
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

        $data = $this->query('PaymentType', $options);

        $data['table'] = View::make('payment_types/_payment_types')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('payment_types/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('payment_types/create');
    }

    public function postCreate()
    {
        return PaymentType::createPaymentType();
    }

    public function getEdit($id)
    {
        $paymentType = PaymentType::findOrFail($id);

        return View::make('payment_types/edit')->with(['paymentType' => $paymentType]);
    }

    public function postEdit($id)
    {
        return PaymentType::editPaymentType($id);
    }

    public function getDelete($id)
    {
        return PaymentType::deletePaymentType($id);
    }
}