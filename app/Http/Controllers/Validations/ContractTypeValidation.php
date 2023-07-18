<?php

namespace App\Http\Controllers\Validations;

class ContractTypeValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,70|unique:contract_types",
            "description" => "required|between:1,400"
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,70|unique:contract_types,name,{$data['id']}",
            "description" => "required|between:1,400"
        ]);
    }
}
