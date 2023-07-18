<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class InterfaceTypesController extends ResourceController
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

        $data = $this->query('InterfaceType', $options);

        $data['table'] = View::make('interface_types/_interface_types')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('interface_types/index')->with($data);
    }
}
