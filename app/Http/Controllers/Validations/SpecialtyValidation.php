<?php

namespace App\Http\Controllers\Validations;

class SpecialtyValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'name' => 'required|between:1,25|unique:specialties',
            'fmv_rate' => 'required|regex:/\d{1,4}\.\d{2}/'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'name' => "required|between:1,25|unique:specialties,name,{$data['id']}",
            'fmv_rate' => 'required|regex:/\d{1,4}\.\d{2}/'
        ]);
    }
}