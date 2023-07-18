<?php

namespace App\Http\Controllers\Validations;

class ContractNameValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'name' => 'required|between:1,35|unique:contract_names'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'name' => "required|between:1,35|unique:contract_names,name,{$data['id']}"
        ]);
    }
}