<?php

namespace App\Http\Controllers;

use App\Specialty;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\SpecialtyValidation;

class SpecialtiesController extends ResourceController
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
            'field_names' => ['name', 'fmv_rate'],
            'per_page' => 9999
        ];

        $data = $this->query('Specialty', $options);
        $data['table'] = View::make('specialties/_specialties')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('specialties/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('specialties/create');
    }

    public function postCreate()
    {
        $validation = new SpecialtyValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $specialty = new Specialty();
        $specialty->name = Request::input('name');
        $specialty->fmv_rate = Request::input('fmv_rate');

        if (!$specialty->save()) {
            return Redirect::back()->with(['error' => Lang::get('specialties.create_error')]);
        }

        return Redirect::route('specialties.index')->with([
            'success' => Lang::get('specialties.create_success')
        ]);
    }

    public function getEdit($id)
    {
        $specialty = Specialty::findOrFail($id);
        return View::make('specialties/edit')->with([
            'specialty' => $specialty
        ]);
    }

    public function postEdit($id)
    {
        $specialty = Specialty::findOrFail($id);

        $validation = new SpecialtyValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $specialty->name = Request::input('name');
        $specialty->fmv_rate = Request::input('fmv_rate');

        if (!$specialty->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('specialties.edit_error')])
                ->withInput();
        }

        return Redirect::route('specialties.index')->with([
            'success' => Lang::get('specialties.edit_success')
        ]);
    }

    public function getDelete($id)
    {
        $specialty = Specialty::findOrFail($id);

        $count = $specialty->practices()->count() +
            $specialty->physicians()->count();

        if ($count > 0) {
            return Redirect::back()->with(['error' => Lang::get('specialties.delete_error')]);
        } else {
            $specialty->delete();
        }

        return Redirect::route('specialties.index')->with([
            'success' => Lang::get('specialties.delete_success')
        ]);
    }
}