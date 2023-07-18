<?php

namespace App\Http\Controllers\Validations;

class HospitalValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'name' => 'required|between:1,50|unique:hospitals',
            'npi' => 'required|digits:10',
            'facility_type' => 'required',
            'address' => 'required|between:1,100',
            'city' => 'required|between:1,35',
            // 'note1'      => 'required',
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'name' => "required|between:1,50|unique:hospitals,name,{$data['id']}",
            'npi' => "required|digits:10|unique:hospitals,npi,{$data['id']}",
            'address' => 'required|between:1,100',
            'city' => 'required|between:1,35',
            'password_expiration_months' => 'required|numeric|digits_between:1,2',
            // 'note1'      => 'required',
        ]);
    }

    public function validateAddAdmin($data)
    {
        return $this->validate($data, [
            'email' => 'required|email|exists:users'
        ]);
    }
}
