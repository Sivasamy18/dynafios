<?php

namespace App\Http\Controllers;

use App\PracticeType;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\PracticeTypeValidation;

class PracticeTypesController extends ResourceController
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

        $data = $this->query('PracticeType', $options);
        $data['table'] = View::make('practice_types/_practice_types')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('practice_types/index')->with($data);
    }

    public function getCreate()
    {
        return View::make('practice_types/create');
    }

    public function postCreate()
    {
        $validation = new PracticeTypeValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $practiceType = new PracticeType();
        $practiceType->name = Request::input('name');
        $practiceType->description = Request::input('description');

        if (!$practiceType->save()) {
            return Redirect::back()->with(['error' => Lang::get('practice_types.create_error')]);
        }

        return Redirect::route('practice_types.index')->with([
            'success' => Lang::get('practice_types.create_success')
        ]);
    }

    public function getEdit($id)
    {
        $practiceType = PracticeType::findOrFail($id);

        return View::make('practice_types/edit')->with(['practiceType' => $practiceType]);
    }

    public function postEdit($id)
    {
        $practiceType = PracticeType::findOrFail($id);

        $validation = new PracticeTypeValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $practiceType->name = Request::input('name');
        $practiceType->description = Request::input('description');

        if (!$practiceType->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('practice_types.edit_error')])
                ->withInput();
        }

        return Redirect::route('practice_types.index')->with([
            'success' => Lang::get('practice_types.edit_success')
        ]);
    }

    public function getDelete($id)
    {
        $practiceType = PracticeType::findOrFail($id);

        if ($practiceType->practices()->count() > 0) {
            return Redirect::back()->with(['error' => Lang::get('practice_types.delete_error')]);
        } else {
            $practiceType->delete();
        }

        return Redirect::route('practice_types.index')->with([
            'success' => Lang::get('practice_types.delete_success')
        ]);
    }
}